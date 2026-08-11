import os
import argparse
import logging
from typing import Optional

import uvicorn
from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

from cad_parser import CADEntityExtractor
from llm_estimator import CADLLMEstimator
from exporter import export_takeoff_to_excel, export_takeoff_to_json
from schemas import DynamicTakeoffResponse

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)

load_dotenv()

app = FastAPI(
    title="Native DWG/DXF AI Estimator API",
    description="High-performance direct DWG vector parser and Gemini LLM volume estimation engine",
    version="2.0.0"
)

# CORS setup for CI4 & React Frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

estimator_engine = CADLLMEstimator()

@app.get("/")
def read_root():
    return {
        "status": "online",
        "app": "Native DWG/DXF AI Estimator API",
        "engine": "ezdwg + ezdxf + Google Gemini LLM",
        "version": "2.0.0"
    }

@app.post("/api/rab/analyze-image")
@app.post("/api/estimate")
async def analyze_image_endpoint(
    name: str = Form(...),
    client: str = Form(...),
    ded_file: UploadFile = File(...)
):
    """
    Main endpoint for CI4 backend integration.
    Processes uploaded DWG, DXF, or CAD/PDF file and returns direct dynamic RAB WBS volume takeoff data from Gemini AI.
    """
    filename = ded_file.filename
    ext = os.path.splitext(filename)[1].lower()
    
    logger.info(f"Received file upload for project '{name}' (Client: '{client}'): {filename}")

    if ext not in [".dwg", ".dxf", ".pdf"]:
        raise HTTPException(
            status_code=400,
            detail="Unsupported file format. Please upload a .dwg, .dxf, or .pdf CAD drawing."
        )

    file_bytes = await ded_file.read()
    if not file_bytes:
        raise HTTPException(status_code=400, detail="Uploaded file is empty.")

    try:
        if ext in [".dwg", ".dxf"]:
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

        # 3. Convert directly to frontend response format (no legacy post-processing mapping)
        response_data = takeoff_result.to_frontend_format()
        response_data["processing_mode"] = processing_mode

        total_work_items = sum(len(wbs.items) for wbs in takeoff_result.wbs_sections)
        logger.info(f"Analysis complete for '{name}'. Generated {len(takeoff_result.wbs_sections)} WBS sections and {total_work_items} work items.")
        return response_data

    except Exception as e:
        logger.error(f"Error processing {filename}: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Failed to process CAD file: {str(e)}")

def main_cli():
    """Command-line interface runner."""
    parser = argparse.ArgumentParser(description="Python Direct DWG AI Estimator CLI")
    parser.add_argument("command", choices=["analyze", "server"], help="Command to execute")
    parser.add_argument("--file", help="Path to input .dwg or .dxf file")
    parser.add_argument("--project", default="Proyek CAD DWG", help="Project Title")
    parser.add_argument("--client", default="Client", help="Client Name")
    parser.add_argument("--excel", help="Output Excel file path (.xlsx)")
    parser.add_argument("--json", help="Output JSON file path (.json)")
    parser.add_argument("--port", type=int, default=8200, help="API server port")

    args = parser.parse_args()

    if args.command == "analyze":
        if not args.file:
            print("Error: --file argument is required for 'analyze' command.")
            return

        print(f"Reading CAD file: {args.file}...")
        with open(args.file, "rb") as f:
            file_bytes = f.read()

        ext = os.path.splitext(args.file)[1].lower()
        if ext in [".dwg", ".dxf"]:
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
        uvicorn.run("main:app", host="0.0.0.0", port=args.port, reload=True)

if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1 and sys.argv[1] in ["analyze", "server"]:
        main_cli()
    else:
        uvicorn.run("main:app", host="0.0.0.0", port=8200, reload=True)
