# 🐍 Estimator API V2 — FastAPI Backend

Backend FastAPI untuk analisis AI konstruksi: ekstraksi kuantitas dari file CAD, BIM, PDF, dan gambar cetak biru, menggunakan **Google Gemini Multimodal** dan **AHSP Vector Semantic Matching**.

---

## 📂 Struktur `api_v2/src/` — Micro-Module Architecture

Seluruh logika bisnis inti diorganisir ke modul-modul kecil dengan satu tanggung jawab:

| File | Baris | Tanggung Jawab |
|---|:---:|---|
| `schemas.py` | ~155 | Pydantic schemas: `DynamicTakeoffResponse`, `EstimateItem`, `BIMElementQuantity`, dll. |
| `prompts.py` | ~300 | System prompts & user prompt builders untuk CAD, PDF, Image, BIM |
| `aps_client.py` | ~450 | Autodesk Platform Services (APS) Cloud API — OAuth, S3 upload, model derivative |
| `bim_parser.py` | ~280 | Parser OpenBIM IFC via `ifcopenshell` & orkestrasi konversi Revit |
| `cad_parser.py` | ~620 | Parser DWG/DXF/DWF/SVG/PLT dengan chain fallback (dwg2dxf → ODA → ezdwg) |
| `llm_estimator.py` | ~490 | Engine eksekusi LLM: fallback chain, JSON repair, Gemini SDK & REST |
| `exporter.py` | ~75 | Export RAB ke Excel (`.xlsx`, 2 sheet) dan JSON flat format |
| `inspect_raw_pipeline.py` | ~300 | CLI debug inspector — jalankan takeoff tanpa server HTTP |

---

## 📡 API Endpoints

### Takeoff (Upload & Analisis File)
| Method | Endpoint | Input | Keterangan |
|---|---|---|---|
| POST | `/api/v2/takeoff/cad` | `.dwg`, `.dxf`, `.svg`, `.plt` | Analisis CAD vector takeoff |
| POST | `/api/v2/takeoff/pdf` | `.pdf` | Analisis PDF set gambar DED |
| POST | `/api/v2/takeoff/image` | `.jpg`, `.png`, `.webp` | Analisis gambar cetak biru |
| POST | `/api/v2/takeoff/bim` | `.ifc`, `.rvt`, `.nwd` | Analisis model 3D BIM |

### AHSP Search & Mapping
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/v2/ahsp/search?q=plesteran` | Cari item AHSP berdasarkan kata kunci |
| POST | `/api/v2/ahsp/map-item` | Peta satu item custom ke AHSP vector DB |

Semua endpoint takeoff mengembalikan **`DynamicTakeoffResponse`** (JSON).

---

## ⚙️ Konfigurasi `.env`

Salin dari `.env.example` lalu isi sesuai kebutuhan:

```env
# === WAJIB ===
GEMINI_API_KEY=your-gemini-api-key-here

# === Opsional: Model Selection ===
GEMINI_MODEL=gemini-2.5-flash

# === Opsional: Primary OpenAI-compatible Proxy ===
PRIMARY_API_BASE=https://your-openai-proxy.com/v1
PRIMARY_API_KEY=your-proxy-key
PRIMARY_MODEL=gpt-4o

# === Opsional: Autodesk Revit (.rvt) Cloud Conversion ===
APS_CLIENT_ID=your-autodesk-client-id
APS_CLIENT_SECRET=your-autodesk-client-secret
RVT_TIMEOUT_SECONDS=300

# === Server Config ===
HOST=0.0.0.0
PORT=8200
MAX_UPLOAD_SIZE_MB=500
```

---

## 🚀 Menjalankan Backend

```bash
cd api_v2

# 1. Buat virtual environment
python3 -m venv .venv
source .venv/bin/activate

# 2. Install dependencies
pip install -r requirements.txt

# 3. Isi konfigurasi
cp .env.example .env

# 4. Jalankan server
python3 main.py server
```

- **Swagger UI**: `http://localhost:8200/docs`
- **ReDoc**: `http://localhost:8200/redoc`

---

## 🖥️ CLI — Analisis Langsung dari Terminal

Untuk menguji takeoff tanpa server HTTP:

```bash
# Analisis file DWG, export ke Excel
python3 main.py analyze --file denah.dwg --project "Rumah Pak Heri" --excel output.xlsx

# Analisis file IFC, export ke JSON
python3 main.py analyze --file model.ifc --json output.json

# Analisis PDF set gambar DED
python3 main.py analyze --file ded_lengkap.pdf --project "Gedung Kantor"

# Jalankan debug inspector pipeline
python3 src/inspect_raw_pipeline.py path/to/drawing.dwg
```

---

## 🧠 Cara Mengubah Aturan QS / Prompt AI

Seluruh instruksi AI berada di satu file: **`src/prompts.py`**

```
src/prompts.py
├── CAD_SYSTEM_PROMPT    → aturan untuk DWG/DXF
├── PDF_SYSTEM_PROMPT    → aturan untuk set gambar PDF multi-halaman
├── IMAGE_SYSTEM_PROMPT  → aturan untuk gambar JPG/PNG
├── BIM_SYSTEM_PROMPT    → aturan untuk model IFC/Revit 3D
└── build_*_user_prompt() → template perintah pengguna
```

Cukup edit konstanta yang relevan — tidak perlu menyentuh kode logika eksekusi LLM.

---

## 📚 Dokumentasi Lanjutan

- **[../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)** — Diagram arsitektur & sequence diagram pipeline
- **[../docs/API_V2_REFERENCE.md](../docs/API_V2_REFERENCE.md)** — Referensi endpoint & skema JSON lengkap
- **[../docs/DEVELOPMENT_GUIDE.md](../docs/DEVELOPMENT_GUIDE.md)** — Panduan setup & kontribusi
