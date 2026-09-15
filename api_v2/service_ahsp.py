"""
AHSP & ChromaDB Microservice — Standalone Vector Search & CrossEncoder Reranking Service.

Isolates memory-heavy AI models (BAAI/bge-m3 SentenceTransformer + BAAI/bge-reranker-v2-m3 CrossEncoder)
and ChromaDB into a single dedicated process on port 8100.
Allows the main API (api_v2 on port 8200) to run with multiple workers (e.g. 5-10 workers)
with minimal RAM footprint (<100MB per worker).
"""

import os
import sys
import time
import logging
from typing import Optional, List, Dict, Any
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException, Form, Query, Request
from fastapi.middleware.cors import CORSMiddleware
from starlette.concurrency import run_in_threadpool
from pydantic import BaseModel
from dotenv import load_dotenv

# Ensure we are in embedded mode for the microservice itself
os.environ["AHSP_MODE"] = "embedded"

# Load environment variables
load_dotenv(override=True)

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - [AHSP-SERVICE] %(levelname)s - %(message)s")
logger = logging.getLogger("service_ahsp")

# Import local mapper engine and schemas
from ahsp.ahsp_mapper import (
    AHSPMapperEngine,
    manual_keyword_search,
    extract_core_keywords,
    parse_ahsp_code_key,
)
from src.schemas import DynamicTakeoffResponse

# Global instance of mapper engine inside this microservice
engine = AHSPMapperEngine()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifecycle manager: Initialize ChromaDB and ML models once at startup."""
    logger.info("Initializing AHSP Mapping Engine (ChromaDB + BGE-M3 + Reranker)...")
    start_t = time.time()
    try:
        engine.initialize()
        # Warm up reranker model at startup so concurrent incoming requests don't race
        engine._get_bge_reranker()
        elapsed = time.time() - start_t
        logger.info(f"AHSP Mapping Engine ready in {elapsed:.2f}s! Total items indexed: {engine._total_items}")
    except Exception as e:
        logger.error(f"Failed to initialize AHSP Mapping Engine: {e}", exc_info=True)
    yield
    logger.info("AHSP Microservice shutting down...")


app = FastAPI(
    title="AHSP & ChromaDB Microservice",
    description="Dedicated Vector Database & Semantic Work Item Mapping Service for Construction AHSP",
    version="1.0.0",
    lifespan=lifespan,
)

# Allow CORS from all sources
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─────────────────────────────────────────────────────────────────────────────
# Request Models
# ─────────────────────────────────────────────────────────────────────────────

class MapSingleItemRequest(BaseModel):
    item_name: str
    item_unit: Optional[str] = ""


class InspectSingleItemRequest(BaseModel):
    item_name: str
    item_unit: Optional[str] = ""
    top_k: int = 5


class SearchRequest(BaseModel):
    query: str
    top_k: int = 5


# ─────────────────────────────────────────────────────────────────────────────
# Endpoints
# ─────────────────────────────────────────────────────────────────────────────

@app.get("/health")
async def health_check():
    """Health check endpoint for main API gateway and container orchestration."""
    return {
        "status": "ok",
        "service": "ahsp_vectordb",
        "ready": engine.is_ready(),
        "total_items": engine._total_items,
        "engine_stats": engine.get_stats(),
    }


@app.post("/api/ahsp/search")
async def search_ahsp(req: Request):
    """
    Semantic vector search for AHSP items.
    Accepts either application/json or multipart/form-data.
    """
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready or initializing.")

    content_type = req.headers.get("content-type", "")
    if "application/json" in content_type:
        body = await req.json()
        query = str(body.get("query", "")).strip()
        top_k = int(body.get("top_k", 5))
    else:
        form = await req.form()
        query = str(form.get("query", "")).strip()
        top_k = int(form.get("top_k", 5))

    if not query:
        return {"query": "", "results": [], "total": 0}

    results = await run_in_threadpool(engine.search, query, top_k=min(top_k, 50))
    return {
        "query": query,
        "results": results,
        "total": len(results),
    }


@app.get("/api/ahsp/search")
async def search_ahsp_keyword(
    q: str = Query(..., description="Query for manual keyword matching"),
    limit: int = Query(100, ge=1, le=500)
):
    """Manual keyword search directly against the indexed AHSP items list."""
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready.")

    raw_q = q.strip()
    core_q = extract_core_keywords(raw_q)

    def _search():
        res = manual_keyword_search(engine._ahsp_items, core_q, limit=limit)
        if not res and core_q != raw_q:
            res = manual_keyword_search(engine._ahsp_items, raw_q, limit=limit)
        return res

    results = await run_in_threadpool(_search)
    return {
        "query": raw_q,
        "core_query": core_q,
        "total_results": len(results),
        "items": results,
    }


@app.post("/api/ahsp/map/single")
async def map_single_item_endpoint(payload: MapSingleItemRequest):
    """Map a single work item (name + unit) to best standard AHSP code."""
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready.")

    result = await run_in_threadpool(engine.map_single_item, payload.item_name, payload.item_unit)
    return result


@app.post("/api/ahsp/inspect/single")
async def inspect_single_item_endpoint(payload: InspectSingleItemRequest):
    """Inspect candidates, score adjustments, and reranking details for a single item."""
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready.")

    result = await run_in_threadpool(
        engine.inspect_single_item, payload.item_name, payload.item_unit, top_k=payload.top_k
    )
    return result


@app.post("/api/ahsp/inspect/takeoff")
async def inspect_takeoff_endpoint(payload: Dict[str, Any]):
    """
    Inspect an entire DynamicTakeoffResponse payload in parallel.
    Maps items and generates the live pipeline inspection report.
    Returns both the inspection report and the mapped takeoff response.
    """
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready.")

    try:
        takeoff_obj = DynamicTakeoffResponse(**payload)
    except Exception as e:
        logger.error(f"Failed to parse takeoff payload: {e}")
        raise HTTPException(status_code=422, detail=f"Invalid takeoff schema: {e}")

    def _process():
        report = engine.inspect_takeoff_response(takeoff_obj)
        mapped = engine.map_takeoff_response(takeoff_obj)
        return report, mapped.model_dump()

    report, mapped_takeoff = await run_in_threadpool(_process)
    return {
        "inspection_report": report,
        "takeoff": mapped_takeoff,
    }


@app.post("/api/ahsp/map/takeoff")
async def map_takeoff_endpoint(payload: Dict[str, Any]):
    """Bulk map an entire DynamicTakeoffResponse without returning full inspection details."""
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready.")

    try:
        takeoff_obj = DynamicTakeoffResponse(**payload)
    except Exception as e:
        raise HTTPException(status_code=422, detail=f"Invalid takeoff schema: {e}")

    mapped = await run_in_threadpool(engine.map_takeoff_response, takeoff_obj)
    return mapped.model_dump()


@app.get("/api/ahsp/items")
async def get_items_endpoint(
    search: Optional[str] = Query(None, description="Optional filter query"),
    limit: int = Query(5000, ge=1, le=10000),
    sort_by_code: bool = Query(True, description="Sort results naturally by AHSP code"),
):
    """Retrieve indexed AHSP master items with optional natural code sorting."""
    if not engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not ready.")

    def _fetch():
        if search and search.strip():
            return manual_keyword_search(engine._ahsp_items, search.strip(), limit=limit, sort_by_code=sort_by_code)
        items_list = [item.to_dict() for item in engine._ahsp_items]
        if sort_by_code:
            items_list.sort(key=lambda x: parse_ahsp_code_key(x.get("id_pekerjaan", "")))
        return items_list[:limit]

    items = await run_in_threadpool(_fetch)
    return {
        "total_available": len(engine._ahsp_items),
        "count": len(items),
        "items": items,
    }


@app.get("/api/ahsp/stats")
async def get_stats_endpoint():
    """Retrieve operational statistics of the vector index and reranker."""
    stats = await run_in_threadpool(engine.get_stats)
    return stats


@app.post("/api/ahsp/reindex")
async def reindex_endpoint():
    """Rebuild the ChromaDB vector index from the master Excel dataset."""
    result = await run_in_threadpool(engine.reindex)
    return result


# ─────────────────────────────────────────────────────────────────────────────
# Standalone CLI Entry Point
# ─────────────────────────────────────────────────────────────────────────────

def main():
    import argparse
    import uvicorn

    parser = argparse.ArgumentParser(description="Run AHSP & ChromaDB Microservice")
    parser.add_argument("--host", default=os.getenv("AHSP_SERVICE_HOST", "0.0.0.0"), help="Host address")
    parser.add_argument("--port", type=int, default=int(os.getenv("AHSP_SERVICE_PORT", "8100")), help="Port number")
    parser.add_argument("--workers", type=int, default=int(os.getenv("AHSP_SERVICE_WORKERS", "1")), help="Number of workers")
    parser.add_argument("--reload", action="store_true", help="Enable hot reload")
    args = parser.parse_args()

    logger.info(f"Starting AHSP Microservice on {args.host}:{args.port} (workers={args.workers})...")
    uvicorn.run(
        "service_ahsp:app",
        host=args.host,
        port=args.port,
        reload=args.reload,
        workers=args.workers,
    )


if __name__ == "__main__":
    main()
