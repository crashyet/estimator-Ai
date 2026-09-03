import os
import json
import time
import logging
from typing import Tuple, Optional, Dict, Any

from fastapi import APIRouter, UploadFile, File, Form, HTTPException
from src.schemas import DynamicTakeoffResponse
from src.cad_parser import CADEntityExtractor
from src.bim_parser import BIMEntityExtractor
from src.llm_estimator import CADLLMEstimator

logger = logging.getLogger(__name__)

router = APIRouter(tags=["Takeoff & LLM Estimation"])

# Initialize LLM Estimator engine
estimator_engine = CADLLMEstimator()

# Check AHSP availability safely
try:
    from ahsp.ahsp_mapper import mapper_engine
    AHSP_AVAILABLE = True
except ImportError:
    mapper_engine = None
    AHSP_AVAILABLE = False

MAX_UPLOAD_SIZE_MB = int(os.getenv("MAX_UPLOAD_SIZE_MB", "500"))
MAX_UPLOAD_BYTES = MAX_UPLOAD_SIZE_MB * 1024 * 1024


async def read_upload_file_with_limit(ded_file: UploadFile) -> bytes:
    """
    Reads upload stream in 1MB chunks to safely handle large BIM/CAD files up to MAX_UPLOAD_SIZE_MB.
    """
    buffer = bytearray()
    chunk_size = 1024 * 1024  # 1MB chunk

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


def apply_ahsp_mapping(takeoff_result: DynamicTakeoffResponse) -> Tuple[DynamicTakeoffResponse, Optional[Dict[str, Any]]]:
    """
    Apply AHSP mapping post-processing to a takeoff response if engine is ready.
    Auto-saves debug log to 'debug_logs/latest_raw_pipeline.json'.
    """
    inspection_report = None
    if AHSP_AVAILABLE and mapper_engine and mapper_engine.is_ready():
        try:
            inspection_report = mapper_engine.inspect_takeoff_response(takeoff_result)
            takeoff_result = mapper_engine.map_takeoff_response(takeoff_result)

            total_items = inspection_report.get("summary", {}).get("total_items", 0)
            high_cnt = inspection_report.get("summary", {}).get("mapped_high", 0)
            med_cnt = inspection_report.get("summary", {}).get("mapped_medium", 0)
            unmap_cnt = inspection_report.get("summary", {}).get("unmapped", 0)

            logger.info("=" * 85)
            logger.info(f"🔍 LIVE RAW PIPELINE INSPECTION FOR PROJECT: '{takeoff_result.project.title}'")
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

            try:
                os.makedirs("debug_logs", exist_ok=True)
                latest_path = os.path.join("debug_logs", "latest_raw_pipeline.json")
                timestamp_path = os.path.join("debug_logs", f"raw_pipeline_{int(time.time())}.json")

                with open(latest_path, "w", encoding="utf-8") as f:
                    json.dump(inspection_report, f, indent=2, ensure_ascii=False)

                with open(timestamp_path, "w", encoding="utf-8") as f:
                    json.dump(inspection_report, f, indent=2, ensure_ascii=False)

                logger.info(f"💾 Saved raw pipeline debug log to '{latest_path}'")
            except Exception as log_err:
                logger.warning(f"Could not save debug log file: {log_err}")

        except Exception as e:
            logger.warning(f"AHSP mapping post-processing failed: {e}")

    return takeoff_result, inspection_report


@router.post("/api/rab/analyze-bim")
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

        takeoff_result, inspection_report = apply_ahsp_mapping(takeoff_result)

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


@router.post("/api/rab/analyze-image")
@router.post("/api/estimate")
async def analyze_image_endpoint(
    name: str = Form(...),
    client: str = Form(...),
    ded_file: UploadFile = File(...)
):
    """
    Main endpoint for CI4 backend & Frontend integration.
    Processes uploaded DWG, DXF, IFC, RVT, SKP, PDF, or Image file and returns dynamic RAB WBS volume takeoff data from Gemini AI.
    """
    filename = ded_file.filename
    ext = os.path.splitext(filename)[1].lower()

    logger.info(f"Received file upload for project '{name}' (Client: '{client}'): {filename}")

    valid_exts = [
        ".dwg", ".dxf", ".dwt", ".dwf", ".dwfx", ".svg", ".plt", ".hpgl", ".hpg",
        ".pdf", ".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp", ".jpeg", ".png", ".jpg"
    ]
    if ext not in valid_exts:
        raise HTTPException(
            status_code=400,
            detail="Unsupported file format. Please upload a valid CAD, BIM, PDF, or image file."
        )

    file_bytes = await read_upload_file_with_limit(ded_file)

    try:
        if ext in [".ifc", ".rvt", ".rfa", ".nwd", ".nwc", ".skp"]:
            logger.info(f"Routing {filename} to OpenBIM/Revit parser...")
            bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, filename)
            bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_bim_payload(
                bim_payload, project_name=name, client_name=client
            )
            processing_mode = f"native_{ext.replace('.', '')}_bim_3d"
        elif ext in [".dwg", ".dxf", ".dwt", ".dwf", ".dwfx", ".svg", ".plt", ".hpgl", ".hpg"]:
            logger.info(f"Parsing native CAD vector format ({ext.upper()})...")
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
            mime_map = {".jpeg": "image/jpeg", ".jpg": "image/jpeg", ".png": "image/png"}
            mime_type = mime_map.get(ext, "image/jpeg")
            logger.info(f"Sending raw image bytes ({len(file_bytes)} bytes) to Gemini LLM Engine...")
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_image_bytes(
                file_bytes, filename=filename, mime_type=mime_type, project_name=name, client_name=client
            )
            processing_mode = f"direct_{ext.replace('.', '')}_multimodal"
        else:
            logger.info(f"Sending raw PDF bytes ({len(file_bytes)} bytes) to Gemini LLM Engine...")
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_pdf_bytes(
                file_bytes, filename=filename, project_name=name, client_name=client
            )
            processing_mode = "direct_pdf_multimodal"

        takeoff_result, inspection_report = apply_ahsp_mapping(takeoff_result)

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
