# 🐍 Python AI Estimator Service (`api_v2`)

Production Python microservice for **Native CAD Parsing** (`.dwg`, `.dxf`) and **Multimodal Vision Takeoff Analysis** (`.pdf`) using **Google Gemini LLM**.

---

## 🚀 Key Features

1. **Direct DWG / DXF Parser (`cad_parser.py`)**:
   - Parses native binary `.dwg` drawings using `ezdwg` (Rust PyO3 engine) and `.dxf` drawings using `ezdxf`.
   - Extracts `TEXT`, `MTEXT`, `DIMENSION` notation, schedule tables, and block attributes grouped by CAD layer names (`S-COLU-TEXT`, `ARCH-DOOR-SCHED`, etc.).
   - Pure vector extraction eliminates GPU requirements and image conversion overhead.

2. **Multimodal LLM Takeoff Engine (`llm_estimator.py`)**:
   - Connects to Google Gemini API (`gemini-2.5-flash`).
   - Analyzes CAD text dumps or multimodal PDF page images to estimate real physical work volumes.
   - Enforces structured JSON output matching WBS Categories A through G.

3. **FastAPI & CLI Engine (`main.py`)**:
   - Exposes REST API endpoints (`/api/rab/analyze-image` and `/api/estimate`).
   - Provides CLI commands for offline processing of DWG files directly to JSON/Excel.

---

## 📂 File Breakdown

```text
api_v2/
├── cad_parser.py           # Native DWG (ezdwg) & DXF (ezdxf) vector extractor
├── llm_estimator.py        # Google Gemini LLM Integration & Vision Batching
├── schemas.py              # Pydantic schemas for request/response validation
├── exporter.py             # Export utility for Excel (.xlsx) and JSON
├── main.py                 # FastAPI server & CLI command dispatcher
├── prd_dwg_ai_estimator.md # Technical Product Requirement Document
├── requirements.txt        # Python package dependencies
├── .env                    # Active environment variables
└── .env.example            # Environment template
```

---

## ⚙️ Environment Variables (`.env`)

Copy `.env.example` to `.env` in `api_v2/`:

```ini
# Google Gemini API Settings
GEMINI_API_KEY=your_google_gemini_api_key_here
GEMINI_MODEL=gemini-2.5-flash

# FastAPI Server Settings
HOST=0.0.0.0
PORT=8200
ALLOWED_ORIGINS=*
```

---

## 💻 Usage & Installation

### 1. Environment Setup
```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

### 2. Run API Server Mode
```bash
python3 main.py server --host 0.0.0.0 --port 8200
```
Server runs at `http://localhost:8200`.

### 3. Run CLI Mode (Offline CAD File Processing)
```bash
# Process a local DWG file and save to Excel / JSON
python3 main.py cli --input sample_plan.dwg --output output_rab.xlsx --export-type excel
```

---

## 📡 Endpoints

### `POST /api/rab/analyze-image`
- **Form Data**:
  - `name`: Project Name
  - `client`: Client Name
  - `ded_file`: Binary Upload (`.dwg`, `.dxf`, `.pdf`)
- **Response**: Structured WBS takeoffs JSON.
