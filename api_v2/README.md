# 🐍 Python AI Estimator Service (`api_v2`)

Production Python microservice for **Native CAD Parsing** (`.dwg`, `.dxf`), **OpenBIM / Revit 3D Takeoff** (`.ifc`, `.rvt`), and **Multimodal Vision Takeoff Analysis** (`.pdf`) using **Google Gemini 2.5 Flash**.

---

## 🚀 Key Features

1. **Direct OpenBIM & Revit 3D Takeoff (`bim_parser.py`)**:
   - Parses OpenBIM `.ifc` files using `ifcopenshell` to extract 3D parametric volumes ($m^3$), net areas ($m^2$), lengths ($m$), storey levels, family types, and materials.
   - Supports native Autodesk Revit `.rvt` files via local converter CLI (`api_v2/bin/rvt2ifc`) or optional Autodesk APS Cloud API.
   - Preserves 100% exact 3D physical volumes while leveraging Gemini 2.5 Flash to map technical family names into Indonesian WBS RAB sections and AHSP codes.

2. **Direct DWG / DXF CAD Parser (`cad_parser.py`)**:
   - Parses native binary `.dwg` drawings using `ezdwg` / `dwg2dxf` and `.dxf` drawings using `ezdxf`.
   - Extracts `TEXT`, `MTEXT`, `DIMENSION` notation, schedule tables, and block attributes grouped by CAD layer names (`S-COLU-TEXT`, `ARCH-DOOR-SCHED`, etc.).

3. **Multimodal LLM Takeoff Engine (`llm_estimator.py`)**:
   - Connects to Google Gemini API (`gemini-2.5-flash`).
   - Analyzes CAD text dumps, 3D BIM payloads, or multimodal PDF page images to estimate real physical work volumes.
   - Enforces structured JSON output matching WBS Categories A through G.

4. **FastAPI & CLI Engine (`main.py`)**:
   - Exposes REST API endpoints (`/api/rab/analyze-bim`, `/api/rab/analyze-image`, and `/api/estimate`).
   - Provides CLI commands for offline processing of `.dwg`, `.dxf`, `.ifc`, `.rvt`, and `.pdf` files directly to JSON/Excel.

---

## 📂 File Breakdown

```text
api_v2/
├── bim_parser.py           # OpenBIM IFC & Revit 3D parametric quantity extractor
├── cad_parser.py           # Native DWG (ezdwg/dwg2dxf) & DXF (ezdxf) vector extractor
├── llm_estimator.py        # Google Gemini LLM Integration & BIM/CAD System Prompts
├── schemas.py              # Pydantic schemas for request/response validation
├── exporter.py             # Export utility for Excel (.xlsx) and JSON
├── main.py                 # FastAPI server & CLI command dispatcher
├── bin/
│   ├── dwg2dxf             # Native AutoCAD DWG to DXF binary converter
│   └── rvt2ifc             # Autodesk Revit RVT to IFC converter script/binary
├── tests/
│   ├── test_bim_parser.py  # Synthetic IFC model generator & parser test suite
│   ├── run_cli_test.py     # CLI execution test runner
│   └── run_rvt_cli.py     # RVT pipeline test runner
├── requirements.txt        # Python package dependencies
├── .env                    # Active environment variables
└── .env.example            # Environment template
```

---

## 🛠️ Binary Converters Tutorial & Setup (`api_v2/bin/`)

For binary CAD (`.dwg`) and Revit (`.rvt`) files, `api_v2` uses a fallback converter pipeline to convert binary files into standard open text vector (`.dxf`) or OpenBIM 3D (`.ifc`) files before parsing.

### 1. `dwg2dxf` (DWG to DXF Converter)
- **Purpose**: Converts native AutoCAD binary `.dwg` drawings into open `.dxf` vector files.
- **Location**: `api_v2/bin/dwg2dxf`
- **Installation & Production Setup**:
  1. Place the compiled `dwg2dxf` binary (from `libredwg` or `ODAFileConverter`) inside `api_v2/bin/dwg2dxf`.
  2. Make the binary executable:
     ```bash
     chmod +x api_v2/bin/dwg2dxf
     ```
  3. System Fallback: If `dwg2dxf` is installed globally on Linux (`/usr/local/bin/dwg2dxf`), `cad_parser.py` will detect it automatically via system `PATH`.

### 2. `rvt2ifc` (Revit RVT to IFC Converter)
- **Purpose**: Converts native Autodesk Revit `.rvt` project files into OpenBIM `.ifc` 3D models.
- **Location**: `api_v2/bin/rvt2ifc`
- **Development Mode**: Pre-configured with a venv-aware dev mock converter script in `api_v2/bin/rvt2ifc` for instant local testing.
- **Production Setup Options**:
  - **Option A (Local Binary Converter - Recommended)**:
    Place a compiled CLI standalone converter (such as `cad2data-Revit-IFC` or `IfcConvert`) at `api_v2/bin/rvt2ifc` and make it executable:
    ```bash
    chmod +x api_v2/bin/rvt2ifc
    ```
  - **Option B (Autodesk APS Cloud API)**:
    Set your Autodesk Platform Services credentials in `.env`:
    ```ini
    APS_CLIENT_ID=your_autodesk_aps_client_id
    APS_CLIENT_SECRET=your_autodesk_aps_client_secret
    ```

---

## ⚙️ Environment Variables (`.env`)

Copy `.env.example` to `.env` in `api_v2/`:

```ini
# Google Gemini API Settings
GEMINI_API_KEY=your_google_gemini_api_key_here
GEMINI_MODEL=gemini-2.5-flash

# Autodesk APS Cloud Settings (Optional for RVT cloud conversion)
APS_CLIENT_ID=your_autodesk_aps_client_id
APS_CLIENT_SECRET=your_autodesk_aps_client_secret

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

### 3. Run CLI Mode (Offline CAD / BIM File Processing)
```bash
# Process a local IFC or RVT file and save to Excel & JSON
python3 main.py analyze --file tests/sample_bim.ifc --project "Gedung Kantor 2 Lantai" --client "PT Beecons Jaya" --excel output_rab.xlsx --json output_rab.json

# Process a local DWG or PDF file
python3 main.py analyze --file drawing.dwg --project "Rumah Tinggal" --client "Klien A" --excel output_rab.xlsx --json output_rab.json
```

---

## 📡 Endpoints

### `POST /api/rab/analyze-bim`
- **Form Data**:
  - `name`: Project Name
  - `client`: Client Name
  - `ded_file`: BIM Upload (`.ifc`, `.rvt`)
- **Response**: Structured 3D BIM WBS takeoff JSON.

### `POST /api/rab/analyze-image` or `POST /api/estimate` (CI4 Integration Endpoint)
- **Form Data**:
  - `name`: Project Name
  - `client`: Client Name
  - `ded_file`: Binary Upload (`.dwg`, `.dxf`, `.ifc`, `.rvt`, `.pdf`)
- **Response**: Unified WBS takeoff JSON (auto-detects extension and routes to appropriate pipeline).
