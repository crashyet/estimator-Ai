"""
main.py — FastAPI Application Entry Point & CLI Command Manager

Menyediakan dua mode penggunaan:
  1. Server Mode  : Menjalankan FastAPI server via Uvicorn (HTTP REST API).
  2. CLI Mode     : Menganalisis file CAD/BIM/PDF/Image langsung dari terminal
                    dan mengekspor hasilnya ke Excel atau JSON tanpa server HTTP.

Router yang terdaftar:
  - takeoff_router: Endpoint /api/v2/takeoff/* untuk upload & analisis file.
  - ahsp_router   : Endpoint /api/v2/ahsp/* untuk pencarian & pemetaan AHSP.

Penggunaan:
  python3 main.py server               # Menjalankan server di port 8200
  python3 main.py analyze --file drawing.dwg --excel output.xlsx
  python3 main.py analyze --file model.ifc  --json  output.json
"""

import os
import argparse
import logging
from contextlib import asynccontextmanager

import anyio.to_thread
import uvicorn
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

from routers.takeoff import router as takeoff_router, estimator_engine
from routers.ahsp import router as ahsp_router
from routers.rab_audit import router as rab_audit_router
from routers.rab_agent import router as rab_agent_router
from src.exporter import export_takeoff_to_excel, export_takeoff_to_json
from src.bim_parser import BIMEntityExtractor
from src.cad_parser import CADEntityExtractor

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)

load_dotenv(override=True)

# Check AHSP Mapper availability
try:
    from ahsp.ahsp_mapper import initialize_mapper
    AHSP_AVAILABLE = True
except ImportError as _ahsp_err:
    logger.warning(f"AHSP Mapper not available: {_ahsp_err}")
    AHSP_AVAILABLE = False


@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Mengelola siklus hidup aplikasi FastAPI.

    Startup:
      - Menyesuaikan kapasitas worker threadpool (AnyIO) untuk menangani multi-request concurrent.
      - Menginisialisasi AHSP Mapping Engine (vector embeddings) jika dependensi tersedia.
      - Jika inisialisasi gagal (non-fatal), server tetap berjalan tanpa fitur AHSP mapping.

    Shutdown:
      - Mencatat log bahwa aplikasi sedang berhenti.
    """
    max_threads = int(os.getenv("MAX_CONCURRENT_THREADS", "100"))
    try:
        limiter = anyio.to_thread.current_default_thread_limiter()
        limiter.total_tokens = max_threads
        logger.info(f"Threadpool capacity configured to {max_threads} concurrent threads.")
    except Exception as e:
        logger.warning(f"Could not adjust AnyIO thread limiter: {e}")

    if AHSP_AVAILABLE:
        ahsp_mode = os.getenv("AHSP_MODE", "remote").lower()
        if ahsp_mode in ("embedded", "local"):
            logger.info("Initializing AHSP Mapping Engine (embedded local mode)...")
        else:
            ahsp_url = os.getenv("AHSP_SERVICE_URL", "http://127.0.0.1:8100")
            logger.info(f"Connecting to AHSP Microservice at {ahsp_url}...")
        try:
            initialize_mapper()
            logger.info("AHSP Mapping Engine connected/initialized successfully.")
        except Exception as e:
            logger.warning(f"AHSP Mapping Engine init failed (non-fatal): {e}")
    else:
        logger.info("AHSP Mapper skipped (dependencies missing).")
    yield
    logger.info("Application shutting down.")


app = FastAPI(
    title="Native DWG/DXF & BIM IFC AI Estimator API",
    description="High-performance direct DWG vector parser, OpenBIM IFC parser, Gemini LLM volume estimation engine, and AHSP Vector Mapping",
    version="2.2.0",
    lifespan=lifespan,
)

# Configuration defaults
DEFAULT_HOST = os.getenv("HOST", "0.0.0.0")
DEFAULT_PORT = int(os.getenv("PORT", 8200))
DEFAULT_WORKERS = int(os.getenv("WORKERS", os.getenv("WEB_CONCURRENCY", "10")))
DEFAULT_RELOAD = os.getenv("RELOAD", "false").lower() in ("true", "1", "yes")
ALLOWED_ORIGINS = [origin.strip() for origin in os.getenv("ALLOWED_ORIGINS", "*").split(",")]
MAX_UPLOAD_SIZE_MB = int(os.getenv("MAX_UPLOAD_SIZE_MB", "500"))
MAX_CONCURRENT_THREADS = int(os.getenv("MAX_CONCURRENT_THREADS", "100"))

# CORS middleware configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Register modular routers
app.include_router(takeoff_router)
app.include_router(ahsp_router)
app.include_router(rab_audit_router)
app.include_router(rab_agent_router)


@app.get("/")
def read_root():
    return {
        "status": "online",
        "app": "Native DWG/DXF & BIM IFC AI Estimator API",
        "engine": "ifcopenshell + ezdwg + ezdxf + Google Gemini LLM",
        "version": "2.2.0",
        "max_upload_size_mb": MAX_UPLOAD_SIZE_MB
    }


def main_cli():
    """Command-line interface runner."""
    parser = argparse.ArgumentParser(description="Python Direct DWG/BIM AI Estimator CLI")
    parser.add_argument("command", choices=["analyze", "server", "start-all"], help="Command to execute")
    parser.add_argument("--file", help="Path to input CAD/BIM/PDF/Image file")
    parser.add_argument("--prompt", help="Deskripsi imajinasi/konsep bangunan dari user via teks")
    parser.add_argument("--project", default="Proyek Estimator", help="Project Title")
    parser.add_argument("--client", default="Client", help="Client Name")
    parser.add_argument("--excel", help="Output Excel file path (.xlsx)")
    parser.add_argument("--json", help="Output JSON file path (.json)")
    parser.add_argument("--port", type=int, default=DEFAULT_PORT, help=f"API server port (default: {DEFAULT_PORT})")
    parser.add_argument("--workers", type=int, default=DEFAULT_WORKERS, help=f"Number of worker processes (default: {DEFAULT_WORKERS})")
    parser.add_argument("--reload", action=argparse.BooleanOptionalAction, default=None, help="Enable/disable auto-reload (default: enabled if workers=1)")

    args = parser.parse_args()

    if args.command == "analyze":
        if args.prompt:
            print(f"Analyzing building imagination prompt: '{args.prompt[:80]}...'")
            takeoff = estimator_engine.analyze_prompt_text(args.prompt, project_name=args.project, client_name=args.client)
        elif args.file:
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
        else:
            print("Error: Either --file or --prompt argument is required for 'analyze' command.")
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

    elif args.command == "start-all":
        import subprocess
        from pathlib import Path
        ahsp_port = int(os.getenv("AHSP_SERVICE_PORT", "8100"))
        logger.info(f"Launching AHSP Microservice on port {ahsp_port}...")
        ahsp_proc = subprocess.Popen(
            [sys.executable, "service_ahsp.py", "--port", str(ahsp_port)],
            cwd=str(Path(__file__).parent),
        )
        try:
            import time
            import requests
            logger.info("Waiting for AHSP Microservice healthcheck...")
            for _ in range(40):
                if ahsp_proc.poll() is not None:
                    logger.error("AHSP Microservice process exited unexpectedly!")
                    return
                try:
                    r = requests.get(f"http://127.0.0.1:{ahsp_port}/health", timeout=1.0)
                    if r.status_code == 200:
                        logger.info(f"AHSP Microservice is online (ready={r.json().get('ready', False)})!")
                        break
                except Exception:
                    pass
                time.sleep(0.5)

            workers = args.workers
            should_reload = False if workers > 1 else (args.reload if args.reload is not None else DEFAULT_RELOAD)
            uvicorn_kwargs = {
                "app": "main:app",
                "host": DEFAULT_HOST,
                "port": args.port,
                "timeout_keep_alive": 300,
                "timeout_graceful_shutdown": 300,
            }
            if should_reload:
                uvicorn_kwargs["reload"] = True
                uvicorn_kwargs["reload_excludes"] = ["*.sqlite3*", "ahsp_vectordb/*", "*.log", "*.wal", "tests/*", "node_modules/*"]
            else:
                uvicorn_kwargs["workers"] = max(1, workers)

            logger.info(f"Starting server on {DEFAULT_HOST}:{args.port} (workers={uvicorn_kwargs.get('workers', 1)}, reload={should_reload})...")
            uvicorn.run(**uvicorn_kwargs)
        finally:
            logger.info("Stopping AHSP Microservice...")
            ahsp_proc.terminate()
            try:
                ahsp_proc.wait(timeout=5)
            except Exception:
                ahsp_proc.kill()

    elif args.command == "server":
        workers = args.workers
        should_reload = False if workers > 1 else (args.reload if args.reload is not None else DEFAULT_RELOAD)
        uvicorn_kwargs = {
            "app": "main:app",
            "host": DEFAULT_HOST,
            "port": args.port,
            "timeout_keep_alive": 300,
            "timeout_graceful_shutdown": 300,
        }
        if should_reload:
            uvicorn_kwargs["reload"] = True
            uvicorn_kwargs["reload_excludes"] = ["*.sqlite3*", "ahsp_vectordb/*", "*.log", "*.wal", "tests/*", "node_modules/*"]
        else:
            uvicorn_kwargs["workers"] = max(1, workers)

        logger.info(f"Starting server on {DEFAULT_HOST}:{args.port} (workers={uvicorn_kwargs.get('workers', 1)}, reload={should_reload})...")
        uvicorn.run(**uvicorn_kwargs)


if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1 and sys.argv[1] in ["analyze", "server", "start-all"]:
        main_cli()
    else:
        workers = DEFAULT_WORKERS
        should_reload = False if workers > 1 else DEFAULT_RELOAD
        uvicorn_kwargs = {
            "app": "main:app",
            "host": DEFAULT_HOST,
            "port": DEFAULT_PORT,
            "timeout_keep_alive": 300,
            "timeout_graceful_shutdown": 300,
        }
        if should_reload:
            uvicorn_kwargs["reload"] = True
            uvicorn_kwargs["reload_excludes"] = ["*.sqlite3*", "ahsp_vectordb/*", "*.log", "*.wal", "tests/*", "node_modules/*"]
        else:
            uvicorn_kwargs["workers"] = max(1, workers)

        logger.info(f"Starting server on {DEFAULT_HOST}:{DEFAULT_PORT} (workers={uvicorn_kwargs.get('workers', 1)}, reload={should_reload})...")
        uvicorn.run(**uvicorn_kwargs)
