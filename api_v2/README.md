# 🐍 Python AI Estimator Service (`api_v2`)

Production Python microservice for **Native CAD Parsing** (`.dwg`, `.dxf`, `.dwt`, `.dwf`, `.dwfx`, `.svg`, `.plt`), **OpenBIM & Autodesk Cloud 3D Takeoff** (`.ifc`, `.rvt`, `.rfa`, `.nwd`, `.nwc`, `.skp`), **VectorDB AHSP Mapping Engine** (SE PUPR 2025), and **Multimodal Vision Takeoff Analysis** (`.pdf`, `.jpeg`, `.png`) using **Google Gemini LLM**.

---

## 🚀 Key Features

1. **AHSP VectorDB Semantic Mapping Engine (`ahsp/ahsp_mapper.py`)**:
   - Integrates 8,900+ official Indonesian SE PUPR 2025 standard construction work items.
   - Vector database engine built on ChromaDB & Sentence-Transformers (`indonesian-roberta-base-indomlen-p1` embeddings).
   - Dynamic item mapping post-processing with strict confidence thresholds:
     - **Mapped High (>= 85%)**: Automated high-precision mapping.
     - **Mapped Medium (65% – 84%)**: Includes top-3 AHSP candidate suggestions.
     - **Unmapped (< 65%)**: Flagged for manual QS verification/override.

2. **100% Real-Data Policy (Zero Dummy Data Guarantee)**:
   - Purged all static dummy numbers, hardcoded fallback dimensions, and sample prompt volumes.
   - All quantities ($m^3$, $m^2$, $m^1$, unit, set, ls) are 100% derived from CAD vector geometry or BIM parametric metadata.
   - Unparseable fields default to `0.0` with explicit `low` confidence warning notes instead of misleading static defaults.

3. **Direct OpenBIM & Cloud 3D Takeoff (`bim_parser.py`)**:
   - Parses OpenBIM `.ifc` files using `ifcopenshell` to extract 3D parametric volumes ($m^3$), net areas ($m^2$), lengths ($m$), storey levels, family types, and materials.
   - Supports native Autodesk Revit (`.rvt`, `.rfa`), Navisworks (`.nwd`, `.nwc`), and SketchUp (`.skp`) files via local converter CLI or Autodesk Platform Services (APS) Model Derivative Cloud API (with GZIP & objecttree fallback).

4. **Direct Vector CAD Parser (`cad_parser.py`)**:
   - Multi-format vector extractor supporting AutoCAD `.dwg`, `.dxf`, `.dwt`, compressed `.dwf`/`.dwfx`, `.svg`, and plot files (`.plt`, `.hpgl`).
   - Extracts `TEXT`, `MTEXT`, `DIMENSION` notation, schedule tables, and block attributes grouped by CAD layer names.

5. **Multimodal LLM Takeoff Engine (`llm_estimator.py`)**:
   - Connects to Google Gemini API (`gemini-2.5-flash`) with fallback model routing.
   - Analyzes CAD text dumps, 3D BIM payloads, or multimodal PDF/Image page streams to calculate physical work quantities.

6. **FastAPI & CLI Engine (`main.py`)**:
   - Exposes REST API endpoints for takeoff analysis and AHSP search/mapping management.
   - CLI mode for offline batch file processing to Excel (.xlsx) and JSON.

---

## 📂 File Breakdown

```text
api_v2/
├── ahsp/
│   ├── ahsp_mapper.py      # ChromaDB + SentenceTransformers vector search engine
│   ├── Item Pekerjaan CK.xlsx # Master dataset (8,900+ SE PUPR 2025 items)
│   └── ahsp_vectordb/      # Persisted ChromaDB embeddings index
├── bim_parser.py           # OpenBIM IFC & APS Cloud 3D parametric quantity extractor
├── cad_parser.py           # Native CAD vector extractor (.dwg, .dxf, .dwf, .svg, .plt)
├── llm_estimator.py        # Google Gemini LLM Integration & Zero-Dummy System Prompts
├── schemas.py              # Pydantic schemas for request/response validation (0.0 volume pass-through)
├── exporter.py             # Export utility for Excel (.xlsx) and JSON
├── main.py                 # FastAPI server & CLI command dispatcher
├── prd_ahsp_mapping.md     # Product requirement document for AHSP mapper engine
├── bin/
│   ├── dwg2dxf             # Native AutoCAD DWG to DXF binary converter
│   └── rvt2ifc             # Autodesk Revit RVT to IFC converter script/binary
├── tests/                  # Integration test suite
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

# Autodesk APS Cloud Settings (Optional for RVT/NWD/SKP cloud conversion)
APS_CLIENT_ID=your_autodesk_aps_client_id
APS_CLIENT_SECRET=your_autodesk_aps_client_secret

# FastAPI Server Settings
HOST=0.0.0.0
PORT=8200
ALLOWED_ORIGINS=*
MAX_UPLOAD_SIZE_MB=500
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

### 3. Run CLI Mode (Offline Processing)
```bash
# Process a local IFC or RVT file and save to Excel & JSON
python3 main.py analyze --file sample_bim.ifc --project "Gedung Kantor" --client "PT Beecons" --excel output_rab.xlsx --json output_rab.json
```

---

## 📡 REST API Endpoints

### 📊 Quantity Takeoff Endpoints
- **`POST /api/rab/analyze-bim`**: Parse 3D BIM/Revit/Navisworks models (`.ifc`, `.rvt`, `.rfa`, `.nwd`, `.nwc`, `.skp`).
- **`POST /api/rab/analyze-image`** / **`POST /api/estimate`**: Unified endpoint for all CAD (`.dwg`, `.dxf`, `.dwt`, `.dwf`, `.plt`), PDF, images, and BIM files.

### 🏷️ AHSP VectorDB Mapping Endpoints
- **`POST /api/ahsp/search`**: Semantic search AHSP items by text query.
- **`POST /api/ahsp/map-item`**: Map a single item name & unit to best matching AHSP code.
- **`GET /api/ahsp/list`**: Paginated list of master AHSP items with search filtering.
- **`POST /api/ahsp/override`**: Manually assign an AHSP code to a work item.
- **`GET /api/ahsp/stats`**: Retrieve AHSP vector DB indexing statistics.
- **`POST /api/ahsp/reindex`**: Force re-indexing of ChromaDB vector database from Excel master.
