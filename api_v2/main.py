import os
import argparse
import logging
from typing import Optional

import uvicorn
from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

from cad_parser import CADEntityExtractor
from bim_parser import BIMEntityExtractor
from llm_estimator import CADLLMEstimator
from exporter import export_takeoff_to_excel, export_takeoff_to_json
from schemas import DynamicTakeoffResponse

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)

load_dotenv()

app = FastAPI(
    title="Native DWG/DXF & BIM IFC AI Estimator API",
    description="High-performance direct DWG vector parser, OpenBIM IFC parser, and Gemini LLM volume estimation engine",
    version="2.1.0"
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

    if ext not in [".ifc", ".rvt"]:
        raise HTTPException(
            status_code=400,
            detail="Unsupported file format for BIM endpoint. Please upload an OpenBIM .ifc or Revit .rvt file."
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

        response_data = takeoff_result.to_frontend_format()
        response_data["processing_mode"] = f"native_{ext.replace('.', '')}_bim_3d"

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
    Processes uploaded DWG, DXF, IFC, RVT, or CAD/PDF file and returns direct dynamic RAB WBS volume takeoff data from Gemini AI.
    """
    filename = ded_file.filename
    ext = os.path.splitext(filename)[1].lower()
    
    logger.info(f"Received file upload for project '{name}' (Client: '{client}'): {filename}")

    if ext not in [".dwg", ".dxf", ".pdf", ".ifc", ".rvt"]:
        raise HTTPException(
            status_code=400,
            detail="Unsupported file format. Please upload a .dwg, .dxf, .ifc, .rvt, or .pdf file."
        )

    file_bytes = await read_upload_file_with_limit(ded_file)

    try:
        if ext in [".ifc", ".rvt"]:
            logger.info(f"Routing {filename} to OpenBIM/Revit parser...")
            bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, filename)
            bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_bim_payload(
                bim_payload, project_name=name, client_name=client
            )
            processing_mode = f"native_{ext.replace('.', '')}_bim_3d"
        elif ext in [".dwg", ".dxf"]:
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
        else:
            logger.info(f"Sending raw PDF bytes ({len(file_bytes)} bytes) directly to Gemini LLM Engine (no image conversion)...")
            takeoff_result: DynamicTakeoffResponse = estimator_engine.analyze_pdf_bytes(
                file_bytes, filename=filename, project_name=name, client_name=client
            )
            processing_mode = "direct_pdf_multimodal"

        # Convert directly to frontend response format
        response_data = takeoff_result.to_frontend_format()
        response_data["processing_mode"] = processing_mode

        total_work_items = sum(len(wbs.items) for wbs in takeoff_result.wbs_sections)
        logger.info(f"Analysis complete for '{name}'. Generated {len(takeoff_result.wbs_sections)} WBS sections and {total_work_items} work items.")
        return response_data

    except Exception as e:
        logger.error(f"Error processing {filename}: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Failed to process file: {str(e)}")

def main_cli():
    """Command-line interface runner."""
    parser = argparse.ArgumentParser(description="Python Direct DWG/BIM AI Estimator CLI")
    parser.add_argument("command", choices=["analyze", "server"], help="Command to execute")
    parser.add_argument("--file", help="Path to input .dwg, .dxf, .ifc, .rvt, or .pdf file")
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
        if ext in [".ifc", ".rvt"]:
            print("Parsing 3D BIM quantities...")
            bim_quantities = BIMEntityExtractor.process_bim_bytes(file_bytes, os.path.basename(args.file))
            bim_payload = BIMEntityExtractor.format_to_llm_payload(bim_quantities)
            print("Mapping BIM 3D quantities with Gemini LLM Engine...")
            takeoff = estimator_engine.analyze_bim_payload(bim_payload, project_name=args.project, client_name=args.client)
        elif ext in [".dwg", ".dxf"]:
            cad_data = CADEntityExtractor.process_file_bytes(file_bytes, os.path.basename(args.file))
            cad_payload = CADEntityExtractor.format_to_llm_payload(cad_data)
            print("Analyzing CAD entities with Gemini LLM Engine...")
            takeoff = estimator_engine.analyze_cad_payload(cad_payload, project_name=args.project, client_name=args.client)
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
