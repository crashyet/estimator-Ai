# 🏗️ AI-Powered Quantity Estimator & RAB Engine

A modern, full-stack AI platform for automatic **Quantity Surveying (QS)**, **100% Real-Data Volumetric Takeoff**, and **AHSP VectorDB Standard Mapping** (SE PUPR 2025) directly from engineering drawings & BIM models (**DWG**, **DXF**, **DWT**, **DWF**, **SVG**, **PLT**, **IFC**, **RVT**, **NWD**, **SKP**, **PDF**, **JPG**).

Driven by direct vector entity extraction (`ezdwg`, `ezdxf`), OpenBIM parametric parsing (`ifcopenshell`, Autodesk APS Cloud), VectorDB semantic mapping (`ChromaDB` + `Sentence Transformers`), and multimodal AI reasoning (**Google Gemini LLM**), this system eliminates manual measurement overhead, generating precise Work Breakdown Structure (WBS) items, real volumetric calculations, and mapped cost estimation tables.

---

## 🏛️ System Architecture

```mermaid
flowchart LR
    User[Client Browser] -->|Upload CAD/BIM/PDF| Frontend[React + Vite Frontend\n:5173]
    Frontend -->|POST /api/rab/analyze-image| Backend[CodeIgniter 4 Backend\n:8080]
    Backend -->|Multipart Proxy Request| FastApi[Python FastAPI Engine\n:8200]
    
    subgraph Python AI Estimator Engine (api_v2)
        FastApi --> Parser{File Extension}
        Parser -->|.dwg / .dxf / .plt / .dwf| VectorEngine[ezdwg / ezdxf / Vector Extractor]
        Parser -->|.ifc / .rvt / .nwd / .skp| BimEngine[ifcopenshell / APS Cloud Engine]
        Parser -->|.pdf / .jpg / .png| VisionEngine[Multimodal Vision Engine]
        
        VectorEngine -->|Structured CAD Payload| Gemini[Google Gemini LLM Engine]
        BimEngine -->|3D Parametric Quantities| Gemini
        VisionEngine -->|Multimodal Visual & Text| Gemini
        
        Gemini -->|Dynamic WBS Takeoff JSON| AhspMapper[AHSP VectorDB Mapper Engine\nChromaDB + PUPR 2025]
    end

    AhspMapper -->|Mapped RAB Response| FastApi
    FastApi -->|Formatted Response| Backend
    Backend -->|Response JSON| Frontend
```

---

## ⚡ Key Features

- **100% Real-Data Guarantee (Zero Dummy Policy)**: Complete removal of static dummy numbers, hardcoded fallback dimensions, and sample prompt volumes. Every quantity ($m^3$, $m^2$, $m^1$, unit, set, ls) is dynamically derived from project metadata and AI vector reasoning.
- **AHSP Semantic VectorDB Mapper (`ChromaDB` + `IndoRoBERTa`)**: Maps extracted work items against 8,900+ official SE PUPR 2025 standard items with precision confidence scoring:
  - **High Confidence (>= 85%)**: Automated high-precision mapping.
  - **Medium Confidence (65% – 84%)**: Top-3 candidate recommendations.
  - **Unmapped (< 65%)**: Flagged for QS manual override.
- **Multi-Format Vector CAD Extraction**: Reads pure vector entities (`TEXT`, `MTEXT`, `DIMENSION`, block schedules) directly from binary `.dwg`, `.dxf`, `.dwt`, `.dwf`, `.dwfx`, `.svg`, and `.plt` files without heavy raster conversion.
- **3D BIM & Cloud Model Conversion**: Parses OpenBIM `.ifc` files directly via `ifcopenshell` and native Revit (`.rvt`, `.rfa`), Navisworks (`.nwd`, `.nwc`), SketchUp (`.skp`) files via local binary CLI or Autodesk Platform Services (APS) Cloud API.
- **Interactive React Dashboard**: High-performance UI for visualizing WBS sections, confidence badges, manual AHSP overrides, and Excel report export.

---

## 📂 Project Structure

```text
estimator/
├── frontend/               # React + Vite Frontend UI (AHSP badges & override modal)
│   ├── src/
│   │   ├── components/     # UI Components (Navbar, Icons, etc.)
│   │   ├── pages/          # Application Pages (Anggaran, Laporan, Project)
│   │   └── services/       # API Service Integration (api.js & AHSP endpoints)
│   ├── .env.example        # Frontend environment template
│   └── package.json
│
├── backend/                # CodeIgniter 4 PHP Backend API
│   ├── app/
│   │   ├── Config/         # App, Database, Routes configuration
│   │   └── Controllers/    # RABController.php (Proxy to Python API)
│   ├── env                 # CI4 environment template
│   └── public/             # Public webroot index.php
│
├── api_v2/                 # Production Python FastAPI Engine (Native CAD, BIM, AHSP VectorDB)
│   ├── ahsp/               # ChromaDB VectorDB & SE PUPR 2025 dataset
│   ├── bim_parser.py       # Direct OpenBIM & APS cloud quantity extractor
│   ├── cad_parser.py       # Direct ezdwg / ezdxf / vector entity extractor
│   ├── llm_estimator.py    # Google Gemini LLM Takeoff engine (Zero-Dummy Prompts)
│   ├── main.py             # FastAPI Server & CLI runner
│   ├── schemas.py          # Pydantic data schemas (0.0 volume pass-through)
│   ├── exporter.py         # Excel & JSON export utility
│   ├── .env.example        # Python service environment template
│   └── requirements.txt    # Python dependencies
│
├── api_v1/                 # Legacy Python FastAPI Engine
└── README.md               # Main System Documentation
```

---

## ⚙️ Environment Setup (`.env`)

Each tier utilizes `.env` files for dynamic configuration. Copy `.env.example` to `.env` in each module directory:

### 1. Python API v2 (`api_v2/.env`)
```ini
# Google Gemini API Settings
GEMINI_API_KEY=your_google_gemini_api_key_here
GEMINI_MODEL=gemini-2.5-flash

# Autodesk APS Cloud Settings (Optional for RVT/NWD cloud conversion)
APS_CLIENT_ID=your_autodesk_aps_client_id
APS_CLIENT_SECRET=your_autodesk_aps_client_secret

# FastAPI Server Settings
HOST=0.0.0.0
PORT=8200
ALLOWED_ORIGINS=*
MAX_UPLOAD_SIZE_MB=500
```

### 2. CodeIgniter 4 Backend (`backend/.env`)
```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

# Python FastAPI AI Service URL
PYTHON_API_URL = http://localhost:8200
```

### 3. Frontend React (`frontend/.env`)
```ini
# Vite Frontend Environment Variables
VITE_API_URL=http://localhost:8080/api/rab/analyze-image
```

---

## 🚀 Getting Started

### 1. Run Python AI Service (`api_v2`)
```bash
cd api_v2
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt

# Start API Server
python3 main.py server --port 8200
```

### 2. Run CodeIgniter 4 Backend (`backend`)
```bash
cd backend
php -d upload_max_filesize=500M -d post_max_size=500M -d memory_limit=1024M -t public -S 0.0.0.0:8080 public/index.php
```

### 3. Run React Frontend (`frontend`)
```bash
cd frontend
npm install
npm run dev
```

The application will be accessible at `http://localhost:5173`.

---

## 📄 License

Internal Development & Proprietary Use - Magang Politeknik Negeri Cilacap - PT. Baracipta Esa Engineering.
