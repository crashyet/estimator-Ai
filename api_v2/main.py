import os
import re
import argparse
import logging
from typing import Optional
from contextlib import asynccontextmanager

import uvicorn
from fastapi import FastAPI, UploadFile, File, Form, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from dotenv import load_dotenv

from cad_parser import CADEntityExtractor
from bim_parser import BIMEntityExtractor
from llm_estimator import CADLLMEstimator
from exporter import export_takeoff_to_excel, export_takeoff_to_json
from schemas import DynamicTakeoffResponse

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)

load_dotenv(override=True)

# --- AHSP Mapper Engine (lazy-loaded at startup) ---
try:
    from ahsp.ahsp_mapper import mapper_engine, initialize_mapper
    AHSP_AVAILABLE = True
except ImportError as _ahsp_err:
    logger.warning(f"AHSP Mapper not available (missing dependencies: {_ahsp_err}). "
                   "Install with: pip install chromadb sentence-transformers")
    mapper_engine = None
    AHSP_AVAILABLE = False


import json
import time
from typing import Tuple, Dict, Any

def _apply_ahsp_mapping(takeoff_result: DynamicTakeoffResponse) -> Tuple[DynamicTakeoffResponse, Optional[Dict[str, Any]]]:
    """
    Apply AHSP mapping post-processing to a takeoff response if engine is ready.
    Generates step-by-step raw pipeline inspection, outputs live terminal debug logs,
    and auto-saves debug log to 'debug_logs/latest_raw_pipeline.json'.
    """
    inspection_report = None
    if AHSP_AVAILABLE and mapper_engine and mapper_engine.is_ready():
        try:
            # 1. Generate step-by-step raw pipeline inspection (Raw AI, VectorDB raw, Reranked raw)
            inspection_report = mapper_engine.inspect_takeoff_response(takeoff_result)

            # 2. Map takeoff_result in-place
            takeoff_result = mapper_engine.map_takeoff_response(takeoff_result)

            # 3. Print Live Terminal Inspection Log
            total_items = inspection_report.get("summary", {}).get("total_items", 0)
            high_cnt = inspection_report.get("summary", {}).get("mapped_high", 0)
            med_cnt = inspection_report.get("summary", {}).get("mapped_medium", 0)
            unmap_cnt = inspection_report.get("summary", {}).get("unmapped", 0)

            logger.info("=" * 85)
            logger.info(f"🔍 LIVE RAW PIPELINE INSPECTION FOR FRONTEND REQUEST: '{takeoff_result.project.title}'")
            logger.info(f"Summary: {total_items} items → Mapped High: {high_cnt}, Medium: {med_cnt}, Unmapped: {unmap_cnt}")
            logger.info("-" * 85)

            for idx, item_insp in enumerate(inspection_report.get("items_pipeline_breakdown", []), 1):
                ai_item = item_insp["ai_raw_item"]
                final_map = item_insp["final_mapping"]
                v_top = item_insp.get("raw_vectordb_candidates", [{}])[0] if item_insp.get("raw_vectordb_candidates") else {}
                r_top = item_insp.get("raw_reranked_candidates", [{}])[0] if item_insp.get("raw_reranked_candidates") else {}

                v_str = f"[{v_top.get('id_pekerjaan', 'None')}] (score {v_top.get('base_score', 0.0)})" if v_top else "N/A"
                r_str = f"[{r_top.get('id_pekerjaan', 'None')}] (score {r_top.get('reranked_score', 0.0)}, delta {r_top.get('score_delta', '0')})" if r_top else "N/A"

                logger.info(
                    f"Item #{idx}: '{ai_item['name']}' ({ai_item['volume']} {ai_item['unit']}) "
                    f"| VectorDB Top-1: {v_str} "
                    f"| Reranked Top-1: {r_str} "
                    f"| STATUS: {final_map.get('ahsp_status', 'unmapped').upper()}"
                )

            logger.info("=" * 85)

            # 4. Auto-save debug JSON to debug_logs directory
            try:
                os.makedirs("debug_logs", exist_ok=True)
                latest_path = os.path.join("debug_logs", "latest_raw_pipeline.json")
                timestamp_path = os.path.join("debug_logs", f"raw_pipeline_{int(time.time())}.json")

                with open(latest_path, "w", encoding="utf-8") as f:
                    json.dump(inspection_report, f, indent=2, ensure_ascii=False)

                with open(timestamp_path, "w", encoding="utf-8") as f:
                    json.dump(inspection_report, f, indent=2, ensure_ascii=False)

                logger.info(f"💾 Auto-saved raw pipeline debug inspection log to '{latest_path}'")
            except Exception as log_err:
                logger.warning(f"Could not auto-save debug log file: {log_err}")

        except Exception as e:
            logger.warning(f"AHSP mapping post-processing failed: {e}")

    return takeoff_result, inspection_report


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan: initialize AHSP engine on startup."""
    if AHSP_AVAILABLE:
        logger.info("Initializing AHSP Mapping Engine at startup...")
        try:
            initialize_mapper()
            logger.info("AHSP Mapping Engine initialized successfully.")
        except Exception as e:
            logger.warning(f"AHSP Mapping Engine init failed (non-fatal): {e}")
    else:
        logger.info("AHSP Mapper skipped (dependencies not installed).")
    yield  # App runs
    logger.info("Application shutting down.")



app = FastAPI(
    title="Native DWG/DXF & BIM IFC AI Estimator API",
    description="High-performance direct DWG vector parser, OpenBIM IFC parser, Gemini LLM volume estimation engine, and AHSP Vector Mapping",
    version="2.2.0",
    lifespan=lifespan,
)

# Environment configurations
DEFAULT_HOST = os.getenv("HOST", "0.0.0.0")
DEFAULT_PORT = int(os.getenv("PORT", 8200))
ALLOWED_ORIGINS = [origin.strip() for origin in os.getenv("ALLOWED_ORIGINS", "*").split(",")]

MAX_UPLOAD_SIZE_MB = int(os.getenv("MAX_UPLOAD_SIZE_MB", "500"))
MAX_UPLOAD_BYTES = MAX_UPLOAD_SIZE_MB * 1024 * 1024

async def read_upload_file_with_limit(ded_file: UploadFile) -> bytes:
    """
    Reads upload stream in 1MB chunks to safely handle large BIM files (up to MAX_UPLOAD_SIZE_MB).
    Raises HTTP 413 (Payload Too Large) if file exceeds configured limit.
    """
    buffer = bytearray()
    chunk_size = 1024 * 1024  # 1MB per chunk

    while chunk := await ded_file.read(chunk_size):
        buffer.extend(chunk)
        if len(buffer) > MAX_UPLOAD_BYTES:
            raise HTTPException(
                status_code=413,
                detail=f"Uploaded file exceeds maximum allowed size limit of {MAX_UPLOAD_SIZE_MB}MB."
            )

    if not buffer:
        raise HTTPException(status_code=400, detail="Uploaded file is empty.")

    return bytes(buffer)

# CORS setup for CI4 & React Frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

estimator_engine = CADLLMEstimator()

@app.get("/")
def read_root():
    return {
        "status": "online",
        "app": "Native DWG/DXF & BIM IFC AI Estimator API",
        "engine": "ifcopenshell + ezdwg + ezdxf + Google Gemini LLM",
        "version": "2.1.0",
        "max_upload_size_mb": MAX_UPLOAD_SIZE_MB
    }

@app.post("/api/rab/analyze-bim")
async def analyze_bim_endpoint(
    name: str = Form(...),
    client: str = Form(...),
    ded_file: UploadFile = File(...)
):
    """
    Endpoint for Revit / OpenBIM 3D Quantity Takeoff (.ifc and .rvt).
    Parses uploaded .ifc / .rvt file and returns standardized WBS volume takeoff data from Gemini AI.
    """
    filename = ded_file.filename
    ext = os.path.splitext(filename)[1].lower()

    logger.info(f"Received BIM file upload for project '{name}' (Client: '{client}'): {filename}")

    if ext not in [".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp"]:
        raise HTTPException(
            status_code=400,
            detail="Unsupported file format for BIM endpoint. Please upload an OpenBIM .ifc, Revit .rvt/.rfa, Navisworks .nwd/.nwc, or SketchUp .skp file."
        )

    file_bytes = await read_upload_file_with_limit(ded_file)

    try:
        logger.info(f"Parsing 3D BIM parametric quantities from {filename}...")
        bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, filename)

        if not bim_quantities:
            raise HTTPException(status_code=400, detail=f"No readable structural/architectural BIM entities found in {filename}.")

        bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)

        logger.info(f"Feeding {len(bim_quantities)} aggregated BIM items payload to Gemini LLM Engine...")
        takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_bim_payload(
            bim_payload, project_name=name, client_name=client
        )

        # Apply AHSP mapping post-processing & generate raw pipeline inspection
        takeoff_result, inspection_report = _apply_ahsp_mapping(takeoff_result)

        response_data = takeoff_result.to_frontend_format()
        response_data["processing_mode"] = f"native_{ext.replace('.', '')}_bim_3d"
        if inspection_report:
            response_data["raw_pipeline_inspection"] = inspection_report

        total_work_items = sum(len(wbs.items) for wbs in takeoff_result.wbs_sections)
        logger.info(f"BIM Analysis complete for '{name}'. Generated {len(takeoff_result.wbs_sections)} WBS sections and {total_work_items} work items.")
        return response_data

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error processing BIM file {filename}: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Failed to process BIM file: {str(e)}")

@app.post("/api/rab/analyze-image")
@app.post("/api/estimate")
async def analyze_image_endpoint(
    name: str = Form(...),
    client: str = Form(...),
    ded_file: UploadFile = File(...)
):
    """
    Main endpoint for CI4 backend integration.
    Processes uploaded DWG, DXF, IFC, RVT, SKP, or CAD/PDF file and returns direct dynamic RAB WBS volume takeoff data from Gemini AI.
    """
    filename = ded_file.filename
    ext = os.path.splitext(filename)[1].lower()
    
    logger.info(f"Received file upload for project '{name}' (Client: '{client}'): {filename}")

    if ext not in [".dwg", ".dxf", ".dwt", ".dwf", ".dwfx", ".svg", ".plt", ".hpgl", ".hpg", ".pdf", ".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp", ".jpeg", ".png", ".jpg"]:
        raise HTTPException(
            status_code=400,
            detail="Unsupported file format. Please upload a .dwg, .dxf, .dwt, .dwf, .dwfx, .svg, .plt, .hpgl, .hpg, .ifc, .rvt, .rfa, .nwd, .nwc, .skp, .pdf, .jpeg, .png, or .jpg file."
        )

    file_bytes = await read_upload_file_with_limit(ded_file)

    try:
        if ext in [".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp"]:
            logger.info(f"Routing {filename} to OpenBIM/Revit/Navisworks/SketchUp parser...")
            bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, filename)
            bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_bim_payload(
                bim_payload, project_name=name, client_name=client
            )
            processing_mode = f"native_{ext.replace('.', '')}_bim_3d"
        elif ext in [".dwg", ".dxf", ".dwt", ".dwf", ".dwfx", ".svg", ".plt", ".hpgl", ".hpg"]:
            logger.info(f"Parsing native CAD vector format ({ext.upper()}) using ezdwg/ezdxf parser...")
            cad_data = CADEntityExtractor.process_file_bytes(file_bytes, filename)
            
            if cad_data.get("error"):
                logger.error(f"CAD Extractor error for {filename}: {cad_data['error']}")
                raise HTTPException(status_code=400, detail=cad_data["error"])

            cad_payload = CADEntityExtractor.format_to_llm_payload(cad_data)

            if not cad_payload.strip():
                raise HTTPException(status_code=400, detail=f"No readable CAD entities or texts found in {filename}.")

            logger.info("Feeding structured CAD payload to Gemini LLM Engine...")
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_cad_payload(
                cad_payload, project_name=name, client_name=client
            )
            processing_mode = f"native_{ext.replace('.', '')}_vector"
        elif ext in [".jpeg", ".png", ".jpg"]:
            mime_map = {
                ".jpeg": "image/jpeg",
                ".jpg": "image/jpeg",
                ".png": "image/png"
            }
            mime_type = mime_map.get(ext, "image/jpeg")
            logger.info(f"Sending raw image bytes ({len(file_bytes)} bytes, {mime_type}) directly to Gemini LLM Engine...")
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_image_bytes(
                file_bytes, filename=filename, mime_type=mime_type, project_name=name, client_name=client
            )
            processing_mode = f"direct_{ext.replace('.', '')}_multimodal"
        else:
            logger.info(f"Sending raw PDF bytes ({len(file_bytes)} bytes) directly to Gemini LLM Engine (no image conversion)...")
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_pdf_bytes(
                file_bytes, filename=filename, project_name=name, client_name=client
            )
            processing_mode = "direct_pdf_multimodal"

        # Apply AHSP mapping post-processing & generate raw pipeline inspection
        takeoff_result, inspection_report = _apply_ahsp_mapping(takeoff_result)

        # Convert directly to frontend response format
        response_data = takeoff_result.to_frontend_format()
        response_data["processing_mode"] = processing_mode
        if inspection_report:
            response_data["raw_pipeline_inspection"] = inspection_report

        total_work_items = sum(len(wbs.items) for wbs in takeoff_result.wbs_sections)
        logger.info(f"Analysis complete for '{name}'. Generated {len(takeoff_result.wbs_sections)} WBS sections and {total_work_items} work items.")
        return response_data

    except Exception as e:
        logger.error(f"Error processing {filename}: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Failed to process file: {str(e)}")

# ─────────────────────────────────────────────────────────────────────
# AHSP Mapping API Endpoints
# ─────────────────────────────────────────────────────────────────────

@app.post("/api/ahsp/search")
async def search_ahsp(
    query: str = Form(..., description="Search query for AHSP item name"),
    top_k: int = Form(5, description="Number of results to return"),
):
    """
    Semantic search AHSP items by name query.
    Returns ranked list of AHSP items with similarity scores.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    results = mapper_engine.search(query.strip(), top_k=min(top_k, 50))
    return {
        "query": query,
        "results": results,
        "total": len(results),
    }


class MapItemRequest(BaseModel):
    item_name: str
    item_unit: Optional[str] = ""


@app.post("/api/ahsp/map-item")
async def map_item_to_ahsp(req: MapItemRequest):
    """
    Map a single work item name to the best matching AHSP code.
    Accepts JSON body: {"item_name": "...", "item_unit": "..."}
    Returns mapping result with confidence status.
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


@app.post("/api/ahsp/inspect-item")
async def inspect_ahsp_item(req: MapItemRequest):
    """
    Inspect raw pipeline steps for a single item (VectorDB raw + Reranked raw + Final mapping).
    Accepts JSON body: {"item_name": "...", "item_unit": "..."}
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    return mapper_engine.inspect_single_item(req.item_name.strip(), (req.item_unit or "").strip(), top_k=5)



@app.get("/api/ahsp/list")
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


@app.post("/api/ahsp/override")
async def override_ahsp_mapping(
    item_id: str = Form(..., description="Work item ID to override"),
    ahsp_code: str = Form(..., description="AHSP code to assign"),
    ahsp_name: str = Form("", description="AHSP name (optional, will be looked up if empty)"),
):
    """
    Manually set AHSP code for a specific work item.
    Returns the override confirmation with looked-up AHSP details.
    """
    if not AHSP_AVAILABLE or not mapper_engine or not mapper_engine.is_ready():
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available or not initialized.")

    # Look up AHSP details if name not provided
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


@app.get("/api/ahsp/stats")
async def ahsp_stats():
    """
    Return AHSP Mapping Engine statistics:
    total indexed items, embedding model info, thresholds, etc.
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


@app.post("/api/ahsp/reindex")
async def reindex_ahsp():
    """
    Force re-index the AHSP vector database from the Excel file.
    Use after updating the Excel master data.
    """
    if not AHSP_AVAILABLE or not mapper_engine:
        raise HTTPException(status_code=503, detail="AHSP Mapping Engine is not available.")

    result = mapper_engine.reindex()
    if result.get("success"):
        return result
    else:
        raise HTTPException(status_code=500, detail=result.get("error", "Re-index failed."))


def parse_ahsp_code_key(code_str: str):
    """
    Parses AHSP code like '1.2.1.1.2' or 'A.2.1.10' into a tuple of integers/strings
    for natural numerical sorting (e.g. 1.2.1.1.2 comes before 1.2.1.1.10).
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
    to isolate the core material / work object (e.g., 'Pengukuran dan Pemasangan Bouwplank' -> 'Bouwplank').
    """
    text = query.strip()
    noise_pattern = r'\b(?:pengukuran|pemasangan|penggalian|pengurugan|pengecoran|pembuatan|pembersihan|plesteran|acian|pengecatan|pembongkaran|penulangan|bekisting|uitzet|perataan|dan|pekerjaan|pasang|gali|urug|cor|buat)\b'
    cleaned = re.sub(noise_pattern, '', text, flags=re.IGNORECASE).strip()
    cleaned = re.sub(r'\s+', ' ', cleaned)
    return cleaned if len(cleaned) >= 2 else text


@app.get("/api/ahsp/search")
async def search_ahsp(q: str = Query(..., description="Nama item pekerjaan AI untuk dicari"), limit: int = Query(20, ge=1, le=100)):
    """
    Opsi 2: Manual Keyword Search based on AI item name.
    Strips filler action verbs to isolate core work object, then performs pure text string matching against master AHSP items.
    (VectorDB & Reranker are exclusively reserved for Opsi 1).
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


@app.get("/api/ahsp/items")
async def get_all_ahsp_items(search: Optional[str] = Query(None, description="Free text search"), limit: int = Query(500, ge=1, le=5000)):
    """
    Opsi 3: Global AHSP Master Database endpoint with pagination & manual text search.
    (VectorDB & Reranker are exclusively reserved for Opsi 1).
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



def main_cli():
    """Command-line interface runner."""
    parser = argparse.ArgumentParser(description="Python Direct DWG/BIM AI Estimator CLI")
    parser.add_argument("command", choices=["analyze", "server"], help="Command to execute")
    parser.add_argument("--file", help="Path to input .dwg, .dxf, .dwt, .dwf, .dwfx, .svg, .plt, .hpgl, .hpg, .ifc, .rvt, .rfa, .nwd, .nwc, .skp, .pdf, .jpeg, .png, or .jpg file")
    parser.add_argument("--project", default="Proyek Estimator", help="Project Title")
    parser.add_argument("--client", default="Client", help="Client Name")
    parser.add_argument("--excel", help="Output Excel file path (.xlsx)")
    parser.add_argument("--json", help="Output JSON file path (.json)")
    parser.add_argument("--port", type=int, default=DEFAULT_PORT, help=f"API server port (default: {DEFAULT_PORT})")

    args = parser.parse_args()

    if args.command == "analyze":
        if not args.file:
            print("Error: --file argument is required for 'analyze' command.")
            return

        print(f"Reading file: {args.file}...")
        with open(args.file, "rb") as f:
            file_bytes = f.read()

        ext = os.path.splitext(args.file)[1].lower()
        if ext in [".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp"]:
            print("Parsing 3D BIM quantities...")
            bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, os.path.basename(args.file))
            bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)
            print("Mapping BIM 3D quantities with Gemini LLM Engine...")
            takeoff = estimator_engine.analyze_bim_payload(bim_payload, project_name=args.project, client_name=args.client)
        elif ext in [".dwg", ".dxf", ".dwt", ".dwf", ".dwfx", ".svg", ".plt", ".hpgl", ".hpg"]:
            cad_data = CADEntityExtractor.process_file_bytes(file_bytes, os.path.basename(args.file))
            cad_payload = CADEntityExtractor.format_to_llm_payload(cad_data)
            print("Analyzing CAD entities with Gemini LLM Engine...")
            takeoff = estimator_engine.analyze_cad_payload(cad_payload, project_name=args.project, client_name=args.client)
        elif ext in [".jpeg", ".png", ".jpg"]:
            mime_map = {".jpeg": "image/jpeg", ".jpg": "image/jpeg", ".png": "image/png"}
            mime_type = mime_map.get(ext, "image/jpeg")
            print(f"Analyzing image document ({mime_type}) directly with Gemini LLM Engine...")
            takeoff = estimator_engine.analyze_image_bytes(file_bytes, filename=os.path.basename(args.file), mime_type=mime_type, project_name=args.project, client_name=args.client)
        elif ext == ".pdf":
            print("Analyzing raw PDF document directly with Gemini LLM Engine...")
            takeoff = estimator_engine.analyze_pdf_bytes(file_bytes, filename=os.path.basename(args.file), project_name=args.project, client_name=args.client)
        else:
            print(f"Error: Unsupported file extension {ext}")
            return

        print("\n--- DIRECT DYNAMIC AI WBS RESULTS SUMMARY ---")
        print(f"Project: {takeoff.project.title} | Client: {takeoff.project.client}")
        print(f"Total WBS Sections: {len(takeoff.wbs_sections)}")
        for wbs in takeoff.wbs_sections:
            print(f"\n=== SECTION [{wbs.section.code}] {wbs.section.name} ===")
            for item in wbs.items:
                print(f"   {item.no}. {item.name}: {item.volume} {item.unit} ({item.warning_note or 'High confidence'})")

        if args.excel:
            export_takeoff_to_excel(takeoff, args.excel)
            print(f"\nExported to Excel: {args.excel}")

        if args.json:
            export_takeoff_to_json(takeoff, args.json)
            print(f"Exported to JSON: {args.json}")

    elif args.command == "server":
        uvicorn.run("main:app", host=DEFAULT_HOST, port=args.port, reload=True, timeout_keep_alive=300, timeout_graceful_shutdown=300)

if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1 and sys.argv[1] in ["analyze", "server"]:
        main_cli()
    else:
        uvicorn.run("main:app", host=DEFAULT_HOST, port=DEFAULT_PORT, reload=True, timeout_keep_alive=300, timeout_graceful_shutdown=300)
