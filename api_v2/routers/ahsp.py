import re
import logging
from typing import Optional, List, Dict, Any

from fastapi import APIRouter, Form, HTTPException, Query
from pydantic import BaseModel

logger = logging.getLogger(__name__)

router = APIRouter(tags=["AHSP Master Data & Mapping"])

# Check AHSP availability safely
try:
    from ahsp.ahsp_mapper import mapper_engine
    AHSP_AVAILABLE = True
except ImportError as _ahsp_err:
    logger.warning(f"AHSP Mapper not available: {_ahsp_err}")
    mapper_engine = None
    AHSP_AVAILABLE = False


class MapItemRequest(BaseModel):
    item_name: str
    item_unit: Optional[str] = ""


def parse_ahsp_code_key(code_str: str):
    """
    Parses AHSP code like '1.2.1.1.2' or 'A.2.1.10' into a tuple of integers/strings
    for natural numerical sorting.
    """
    if not code_str:
        return ()
    parts = re.split(r'[\.\-\/\s]+', str(code_str).strip())
    key = []
    for p in parts:
        if p.isdigit():
            key.append((0, int(p)))
        else:
            key.append((1, p.lower()))
    return tuple(key)


def manual_keyword_search(items: list, query: str, limit: int = 50, sort_by_code: bool = True) -> list:
    """
    Perform direct manual keyword matching against AHSP items without VectorDB / Reranker.
    Sorts matches by AHSP code number.
    """
    if not query or not query.strip():
        items_list = [item.to_dict() for item in items]
        if sort_by_code:
            items_list.sort(key=lambda x: parse_ahsp_code_key(x.get("id_pekerjaan", "")))
        return items_list[:limit]

    query_clean = query.strip().lower()
    keywords = [k for k in query_clean.split() if k]

    exact_matches = []
    all_keywords_matches = []
    partial_keywords_matches = []

    for item in items:
        name_lower = item.nama_pekerjaan.lower()
        code_lower = item.id_pekerjaan.lower()
        item_dict = item.to_dict()

        if query_clean in name_lower or query_clean in code_lower:
            exact_matches.append(item_dict)
            continue

        if len(keywords) > 1 and all(kw in name_lower or kw in code_lower for kw in keywords):
            all_keywords_matches.append(item_dict)
            continue

        if any(kw in name_lower or kw in code_lower for kw in keywords):
            partial_keywords_matches.append(item_dict)

    if sort_by_code:
        exact_matches.sort(key=lambda x: parse_ahsp_code_key(x.get("id_pekerjaan", "")))
        all_keywords_matches.sort(key=lambda x: parse_ahsp_code_key(x.get("id_pekerjaan", "")))
        partial_keywords_matches.sort(key=lambda x: parse_ahsp_code_key(x.get("id_pekerjaan", "")))

    combined = exact_matches + all_keywords_matches + partial_keywords_matches
    return combined[:limit]


def extract_core_keywords(query: str) -> str:
    """
    Strips noise & filler verbs/words (pemasangan, pengukuran, dan, uitzet, pembuatan, dll)
    to isolate the core material / work object.
    """
    text = query.strip()
    noise_pattern = r'\b(?:pengukuran|pemasangan|penggalian|pengurugan|pengecoran|pembuatan|pembersihan|plesteran|acian|pengecatan|pembongkaran|penulangan|bekisting|uitzet|perataan|dan|pekerjaan|pasang|gali|urug|cor|buat)\b'
    cleaned = re.sub(noise_pattern, '', text, flags=re.IGNORECASE).strip()
    cleaned = re.sub(r'\s+', ' ', cleaned)
    return cleaned if len(cleaned) >= 2 else text


@router.post("/api/ahsp/search")
async def search_ahsp_post(
    query: str = Form(..., description="Search query for AHSP item name"),
    top_k: int = Form(5, description="Number of results to return"),
):
    """
    Semantic search AHSP items by name query (POST).
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    results = mapper_engine.search(query.strip(), top_k=min(top_k, 50))
    return {
        "query": query,
        "results": results,
        "total": len(results),
    }


@router.get("/api/ahsp/search")
async def search_ahsp_get(
    q: str = Query(..., description="Nama item pekerjaan AI untuk dicari"),
    limit: int = Query(100, ge=1, le=500)
):
    """
    Manual Keyword Search based on AI item name (GET).
    Strips filler action verbs to isolate core work object, then performs text string matching against master AHSP items.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available.")

    raw_q = q.strip()
    core_q = extract_core_keywords(raw_q)

    results = manual_keyword_search(mapper_engine._ahsp_items, core_q, limit=limit)
    if not results and core_q != raw_q:
        results = manual_keyword_search(mapper_engine._ahsp_items, raw_q, limit=limit)

    return {
        "query": raw_q,
        "core_query": core_q,
        "total_results": len(results),
        "items": results
    }


@router.post("/api/ahsp/map-item")
async def map_item_to_ahsp(req: MapItemRequest):
    """
    Map a single work item name to the best matching AHSP code.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    mapping = mapper_engine.map_single_item(req.item_name.strip(), (req.item_unit or "").strip())
    return {
        "input": {"item_name": req.item_name, "item_unit": req.item_unit},
        "ahsp_code": mapping["ahsp_code"],
        "ahsp_name": mapping["ahsp_name"],
        "ahsp_unit": mapping["ahsp_unit"],
        "ahsp_score": mapping["ahsp_score"],
        "ahsp_status": mapping["ahsp_status"],
        "ahsp_candidates": mapping["ahsp_candidates"],
        "mapping": mapping,
    }


@router.post("/api/ahsp/inspect-item")
async def inspect_ahsp_item(req: MapItemRequest):
    """
    Inspect raw pipeline steps for a single item (VectorDB raw + Reranked raw + Final mapping).
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    return mapper_engine.inspect_single_item(req.item_name.strip(), (req.item_unit or "").strip(), top_k=5)


@router.get("/api/ahsp/list")
async def list_ahsp_items(
    page: int = Query(1, ge=1, description="Page number"),
    limit: int = Query(50, ge=1, le=200, description="Items per page"),
    search: str = Query("", description="Optional search filter"),
):
    """
    Paginated list of all AHSP items. Optionally filter by search query.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    return mapper_engine.get_all_items(page=page, limit=limit, search_query=search)


@router.get("/api/ahsp/items")
async def get_all_ahsp_items(
    search: Optional[str] = Query(None, description="Free text search"),
    limit: int = Query(500, ge=1, le=5000)
):
    """
    Global AHSP Master Database endpoint with pagination & manual text search.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available.")

    if search and search.strip():
        results = manual_keyword_search(mapper_engine._ahsp_items, search.strip(), limit=limit)
        return {
            "search": search.strip(),
            "total_results": len(results),
            "items": results
        }

    all_items = [item.to_dict() for item in mapper_engine._ahsp_items[:limit]]
    return {
        "total_items": len(mapper_engine._ahsp_items),
        "returned": len(all_items),
        "items": all_items
    }


@router.post("/api/ahsp/override")
async def override_ahsp_mapping(
    item_id: str = Form(..., description="Work item ID to override"),
    ahsp_code: str = Form(..., description="AHSP code to assign"),
    ahsp_name: str = Form("", description="AHSP name (optional, will be looked up if empty)"),
):
    """
    Manually set AHSP code for a specific work item.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    if not ahsp_name:
        results = mapper_engine.search(ahsp_code, top_k=1)
        if results:
            ahsp_name = results[0].get("nama_pekerjaan", ahsp_code)

    return {
        "item_id": item_id,
        "override": {
            "ahsp_code": ahsp_code,
            "ahsp_name": ahsp_name,
            "ahsp_status": "mapped_high",
            "ahsp_score": 1.0,
        },
        "message": f"AHSP override applied: {item_id} → {ahsp_code}",
    }


@router.get("/api/ahsp/stats")
async def ahsp_stats():
    """
    Return AHSP Mapping Engine statistics.
    """
    if not AHSP_AVAILABLE:
        return {
            "available": False,
            "message": "AHSP dependencies not installed. Run: pip install chromadb sentence-transformers",
        }

    if not mapper_engine:
        return {"available": False, "message": "AHSP Mapper engine not loaded."}

    stats = mapper_engine.get_stats()
    stats["available"] = True
    return stats


@router.post("/api/ahsp/reindex")
async def reindex_ahsp():
    """
    Force re-index the AHSP vector database from the Excel file.
    """
    if not AHSP_AVAILABLE or not mapper_engine:
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available.")

    result = mapper_engine.reindex()
    if result.get("success"):
        return result
    else:
        raise HTTPException(status_code=500, detail=result.get("error", "Re-index failed."))
