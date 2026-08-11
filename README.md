# 🏗️ AI-Powered Quantity Estimator & RAB Engine

A modern, full-stack AI platform for automatic **Quantity Surveying (QS)** and **Bill of Quantities (BOQ / RAB)** generation directly from engineering drawings (Detail Engineering Design / DED in **DWG**, **DXF**, and **PDF** formats).

Driven by direct vector entity extraction (`ezdwg`, `ezdxf`) and multimodal AI vision reasoning (**Google Gemini LLM**), this system eliminates manual measurement overhead, generating precise Work Breakdown Structure (WBS) items, real volumetric calculations, and formatted cost estimation tables.

---

## 🏛️ System Architecture

```mermaid
flowchart LR
    User[Client Browser] -->|Upload .dwg / .dxf / .pdf| Frontend[React + Vite Frontend\n:5173]
    Frontend -->|POST /api/rab/analyze-image| Backend[CodeIgniter 4 Backend\n:8080]
    Backend -->|Multipart Proxy Request| FastApi[Python FastAPI Engine\n:8200]
    
    subgraph Python AI Estimator Engine (api_v2)
        FastApi --> Parser{File Type}
        Parser -->|.dwg / .dxf| VectorEngine[ezdwg / ezdxf Direct Vector Engine]
        Parser -->|.pdf| PdfEngine[PyMuPDF / Multimodal Engine]
        VectorEngine -->|Structured CAD Dump| Gemini[Google Gemini LLM Engine]
        PdfEngine -->|Multimodal Visual & Text| Gemini
    end

    Gemini -->|Dynamic WBS Takeoff JSON| FastApi
    FastApi -->|Formatted Response| Backend
    Backend -->|Response JSON| Frontend
```

---

## ⚡ Key Features

- **Native DWG/DXF Parsing (`ezdwg` + `ezdxf`)**: Reads pure vector entities (`TEXT`, `MTEXT`, `DIMENSION`, block schedules) directly from binary `.dwg` files without heavy PDF/Image conversion.
- **Multimodal PDF Drawing Vision Analysis**: Processes multi-page technical drawings directly with Gemini multimodal vision capabilities.
- **Dynamic WBS Categorization**: Automatically groups items into civil engineering standard categories (A: Persiapan, B: Tanah & Pondasi, C: Struktur Beton, D: Arsitektur & Finishing, E: Atap & Plafon, F: MEP, G: Lain-lain).
- **Environment-Driven Configuration (`.env`)**: Fully dynamic, non-static port, host, API endpoint, and secret key configurations.
- **Full Stack Architecture**: Interactive React dashboard for visualizing, editing, and exporting RAB budgets.

---

## 📂 Project Structure

```text
estimator/
├── frontend/               # React + Vite Frontend UI
│   ├── src/
│   │   ├── components/     # UI Components (Navbar, Icons, etc.)
│   │   ├── pages/          # Application Pages (Anggaran, Laporan, Project)
│   │   └── services/       # API Service Integration (api.js)
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
├── api_v2/                 # Production Python FastAPI Engine (Native CAD + Gemini)
│   ├── cad_parser.py       # Direct ezdwg / ezdxf vector entity extractor
│   ├── llm_estimator.py    # Google Gemini LLM Takeoff engine
│   ├── main.py             # FastAPI Server & CLI runner
│   ├── schemas.py          # Pydantic data schemas
│   ├── exporter.py         # Excel & JSON export utility
│   ├── .env.example        # Python service environment template
│   └── requirements.txt    # Python dependencies
│
├── api_v1/                 # Legacy Python FastAPI Engine (ChromaDB + Groq Llama)
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

# FastAPI Server Settings
HOST=0.0.0.0
PORT=8200
ALLOWED_ORIGINS=*
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
php spark serve --port 8080
```

### 3. Run React Frontend (`frontend`)
```bash
cd frontend
npm install
npm run dev
```

The application will be accessible at `http://localhost:5173`.

---

## 📡 API Reference

### Analyze DED / CAD File

- **Endpoint**: `POST /api/rab/analyze-image` (via CI4 Backend or direct to Python API)
- **Content-Type**: `multipart/form-data`

#### Request Parameters
| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `name` | `string` | Yes | Construction project title |
| `client` | `string` | Yes | Client or owner name |
| `ded_file` | `file` | Yes | Binary file (`.dwg`, `.dxf`, or `.pdf`) |

#### Sample Response (`200 OK`)
```json
{
  "project": {
    "title": "Pembangunan Rumah Type 36",
    "client": "Klien Mandiri",
    "status": "Perencanaan"
  },
  "items": [
    {
      "id": "sec-A",
      "type": "section",
      "code": "A",
      "name": "PEKERJAAN PERSIAPAN & K3"
    },
    {
      "id": "item-A-0",
      "type": "item",
      "sectionCode": "A",
      "no": 1,
      "code": "A.1",
      "name": "Pembersihan Lapangan & Pembuatan Bouwplank",
      "volume": 60.0,
      "unit": "m2",
      "confidence": "high",
      "warning_note": "Berdasarkan luas denah 6.0m x 10.0m"
    }
  ],
  "processing_mode": "native_dwg_vector"
}
```

---

## 📄 License

Internal Development & Proprietary Use - Beecons Nusantara.
