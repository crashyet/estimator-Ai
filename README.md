# 🏗️ AI Construction Estimator — WBS Takeoff & AHSP Mapper

> **Sistem estimasi biaya konstruksi berbasis AI** yang menganalisis gambar teknik (CAD, BIM, PDF, Image) dan menghasilkan **Rencana Anggaran Biaya (RAB)** terstruktur berstandar Indonesia, lengkap dengan pemetaan otomatis ke database AHSP nasional.

---

## ✨ Fitur Utama

| Fitur | Deskripsi |
|---|---|
| 📐 **CAD Takeoff** | Parse DWG/DXF/SVG/PLT secara native, ekstrak layer, dimensi, block attribute |
| 🏢 **BIM Takeoff** | Parse IFC via `ifcopenshell`; konversi Revit `.rvt` via Autodesk Cloud (APS) |
| 📄 **PDF Takeoff** | Analisis multimodal set gambar DED multi-halaman via Gemini Vision |
| 🖼️ **Image Takeoff** | Analisis cetak biru arsitektur (JPG/PNG) via Gemini Vision |
| 🤖 **Gemini AI Engine** | Multi-tier fallback: Primary API → Gemini SDK → Gemini REST |
| 🇮🇩 **AHSP Mapper** | Semantic embedding matching ke database AHSP Indonesia |
| 📊 **Dashboard RAB** | UI interaktif React + AI candidate popover per item pekerjaan |
| 📥 **Export** | Export RAB ke Excel (`.xlsx`) atau JSON |

---

## 🏗️ Arsitektur Sistem

Alur data berjalan dari **Frontend → Backend (CI4) → API V2**:

```
[User — React Dashboard (frontend/)]
         │  Upload file + form data
         ▼
[Backend — CodeIgniter 4 (backend/)]
         │  API Gateway & Request Proxy
         │  POST /api/rab/analyze → forward ke Python API
         ▼
[API V2 — FastAPI Python (api_v2/)]
         │
         ├─ src/cad_parser   → ekstraksi entitas vektor DWG/DXF
         ├─ src/bim_parser   → ekstraksi kuantitas IFC/Revit
         ├─ src/aps_client   → Autodesk Cloud konversi Revit
         ├─ src/prompts      → AI System Prompts (aturan QS Indonesia)
         ├─ src/llm_estimator→ Gemini AI Engine (multi-tier fallback)
         └─ src/schemas      → Pydantic Validation
         │
         ▼
[AHSP Vector DB — Semantic Mapping]
         │
         ▼
[JSON DynamicTakeoffResponse → Frontend]
```

Lihat **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** untuk diagram sequence lengkap.

---

## 📁 Struktur Direktori

```
estimator/
├── frontend/                        # ⚛️  Frontend React 18 + Vite (UI Dashboard)
│   ├── package.json
│   └── src/
│       ├── pages/
│       │   ├── Project.jsx          # Daftar proyek
│       │   ├── Anggaran.jsx         # RAB detail + AI candidate popover
│       │   └── Laporan.jsx          # Laporan analitik & export
│       └── components/              # Komponen UI reusable
│
├── backend/                         # 🐘 Backend Gateway CodeIgniter 4 (PHP Gateway)
│   ├── app/
│   │   ├── Controllers/
│   │   │   └── RABController.php    # Proxy controller forwarding request ke Python API
│   │   └── Config/
│   │       └── Routes.php           # Routing API CodeIgniter
│   ├── public/                      # Web root (index.php)
│   └── .env                         # Konfigurasi CI4 (PYTHON_API_URL, dll.)
│
├── api_v2/                          # 🐍 AI Engine FastAPI Python (V2 Microservice)
│   ├── main.py                      # Entry point server & CLI runner
│   ├── requirements.txt             # Python dependencies
│   ├── .env.example                 # Template variabel lingkungan
│   ├── ahsp/                        # Vector DB & semantic mapper AHSP
│   │   └── ahsp_mapper.py           # Core embedding search engine
│   ├── routers/                     # Route REST API
│   │   ├── takeoff.py               # Endpoint takeoff (CAD/BIM/PDF/Image)
│   │   └── ahsp.py                  # Endpoint pencarian & mapping AHSP
│   └── src/                         # Micro-modules logika bisnis (< 500 baris/file)
│       ├── schemas.py               # Pydantic schemas (DynamicTakeoffResponse, dll.)
│       ├── prompts.py               # System prompts & user prompt builders
│       ├── aps_client.py            # Autodesk Platform Services (APS) API client
│       ├── bim_parser.py            # OpenBIM IFC parser (ifcopenshell)
│       ├── cad_parser.py            # CAD DWG/DXF/SVG/PLT parser
│       ├── llm_estimator.py         # Gemini LLM execution engine
│       ├── exporter.py              # Excel & JSON exporter
│       └── inspect_raw_pipeline.py  # CLI debug inspector tool
│
├── docs/                            # 📚 Dokumentasi teknis terpusat
│   ├── ARCHITECTURE.md              # Diagram arsitektur & alur data pipeline
│   ├── API_V2_REFERENCE.md          # Referensi lengkap REST API V2
│   ├── DEVELOPMENT_GUIDE.md         # Panduan setup & pengembangan
│   └── modules/                     # Dokumentasi terpisah per modul src/
│       ├── schemas.md
│       ├── prompts.md
│       ├── llm_estimator.md
│       ├── bim_parser.md
│       ├── cad_parser.md
│       └── aps_client_and_exporter.md
│
└── README.md                        # Dokumentasi utama proyek ini
```

---

## 🚀 Quick Start

Jalankan ketiga layanan secara berurutan:

### 1. Frontend (`frontend/`)
```bash
cd frontend
npm install
npm run dev -- --host
```
> Dashboard tersedia di `http://localhost:5173`

### 2. Backend Gateway (`backend/`)
```bash
cd backend
cp env .env
# Pastikan PYTHON_API_URL=http://localhost:8200 di .env

php spark serve --port 8080
```
> Backend gateway berjalan di `http://localhost:8080`

### 3. AI Engine API (`api_v2/`)
```bash
cd api_v2
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
# Isi GEMINI_API_KEY di file .env

python3 main.py server
```
> API V2 berjalan di `http://localhost:8200`
> Swagger UI tersedia di `http://localhost:8200/docs`

---

## ⚙️ Environment Variables

### `api_v2/.env`

| Variabel | Wajib | Default | Keterangan |
|---|:---:|:---:|---|
| `GEMINI_API_KEY` | ✅ | — | API Key Google Gemini |
| `GEMINI_MODEL` | ❌ | `gemini-2.5-flash` | Model Gemini yang digunakan |
| `PRIMARY_API_BASE` | ❌ | — | URL proxy API OpenAI-compatible |
| `PRIMARY_API_KEY` | ❌ | — | API Key untuk primary proxy |
| `APS_CLIENT_ID` | ❌ | — | Autodesk Platform Services Client ID |
| `APS_CLIENT_SECRET` | ❌ | — | Autodesk Platform Services Client Secret |
| `HOST` | ❌ | `0.0.0.0` | Host server |
| `PORT` | ❌ | `8200` | Port server |

### `backend/.env`

| Variabel | Keterangan |
|---|---|
| `PYTHON_API_URL` | URL ke FastAPI AI Engine (default: `http://localhost:8200`) |
| `app.baseURL` | URL base backend CI4 (default: `http://localhost:8080/`) |

---

## 🛠️ Tech Stack

**Frontend**: React 18 · Vite · TailwindCSS · Lucide React · React Router

**Backend Gateway**: PHP 8.2+ · CodeIgniter 4 · cURL

**AI Engine**: Python 3.10+ · FastAPI · Uvicorn · Pydantic V2 · ifcopenshell · ezdxf · ezdwg · Google Generative AI SDK · pandas · openpyxl

---

## 📚 Dokumentasi Teknis

| Dokumen | Isi |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Diagram arsitektur sistem, sequence diagram pipeline, tabel modul |
| [docs/API_V2_REFERENCE.md](docs/API_V2_REFERENCE.md) | Spesifikasi endpoint REST API, skema request/response, env vars |
| [docs/DEVELOPMENT_GUIDE.md](docs/DEVELOPMENT_GUIDE.md) | Setup lokal, CLI debug tool, cara modifikasi prompt AI |
| [docs/modules/](docs/modules/) | Dokumentasi per modul `api_v2/src/` |
| [frontend/README.md](frontend/README.md) | Panduan spesifik frontend dashboard |
| [backend/README.md](backend/README.md) | Panduan spesifik backend gateway CI4 |
| [api_v2/README.md](api_v2/README.md) | Panduan spesifik AI Engine API V2 |
