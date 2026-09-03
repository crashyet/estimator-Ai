# Panduan Developer & Maintenance

Panduan ini berisi instruksi setup lokal, debug pipeline, pengembangan prompt AI, dan konvensi kode untuk proyek **Estimator AI**.

---

## 1. Setup Pengembangan Lokal

### Prasyarat

| Komponen | Versi Minimal | Keterangan |
|---|---|---|
| **Node.js** | v18+ | Untuk frontend React/Vite |
| **Python** | 3.10+ | Untuk AI Engine FastAPI |
| **PHP** | 8.2+ | Untuk backend gateway CI4 |
| **PHP Extensions** | — | `curl`, `json`, `mbstring`, `intl` |
| `dwg2dxf` / LibreDWG | opsional | Konversi DWG ke DXF (CAD) |
| `ifcopenshell` | opsional | Sudah termasuk di `requirements.txt` |

---

### Langkah 1 — Frontend (`frontend/`)

```bash
cd frontend

# Install dependencies
npm install

# Jalankan dev server (accessible dari semua network interface)
npm run dev -- --host
```

> Dashboard tersedia di `http://localhost:5173`

---

### Langkah 2 — Backend Gateway (`backend/`)

```bash
cd backend

# Copy template environment
cp env .env
```

Edit `.env`, pastikan variabel berikut sudah benar:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

# URL Python AI Engine
PYTHON_API_URL = http://localhost:8200
```

```bash
# Jalankan server built-in CI4
php spark serve --port 8080
```

> Backend gateway berjalan di `http://localhost:8080`

---

### Langkah 3 — AI Engine (`api_v2/`)

```bash
cd api_v2

# Buat virtual environment
python3 -m venv .venv
source .venv/bin/activate   # Linux/macOS
# .venv\Scripts\activate    # Windows

# Install dependencies
pip install -r requirements.txt

# Copy template environment
cp .env.example .env
```

Edit `.env`, isi setidaknya:

```env
GEMINI_API_KEY=your-gemini-api-key-here
```

```bash
# Jalankan server FastAPI
python3 main.py server
```

> AI Engine berjalan di `http://localhost:8200`
> Swagger UI tersedia di `http://localhost:8200/docs`

---

## 2. CLI Tooling & Debug Pipeline

Untuk menguji parsing CAD dan LLM takeoff secara langsung **tanpa menjalankan HTTP server**, gunakan script CLI inspector di `src/inspect_raw_pipeline.py`:

```bash
cd api_v2
source .venv/bin/activate

# Test file DWG
python3 src/inspect_raw_pipeline.py path/to/drawing.dwg

# Test file IFC
python3 src/inspect_raw_pipeline.py path/to/model.ifc

# Test file PDF
python3 src/inspect_raw_pipeline.py path/to/ded.pdf
```

Script akan:
1. Mengekstrak raw entity dan notasi teks WBS dari file.
2. Menyimpan debug log ke `debug_logs/raw_pipeline_<timestamp>.json`.
3. Menampilkan jumlah seksi WBS dan estimasi volume di console.

Gunakan juga CLI dari `main.py` untuk export langsung:

```bash
# Analisis DWG, export ke Excel
python3 main.py analyze --file denah.dwg --project "Rumah Pak Heri" --excel output.xlsx

# Analisis IFC, export ke JSON
python3 main.py analyze --file model.ifc --json output.json
```

---

## 3. Mengubah Aturan QS di `src/prompts.py`

Seluruh instruksi sistem AI untuk Quantity Surveying Indonesia ada di satu file: `api_v2/src/prompts.py`.

### Cara menambah atau mengubah aturan QS:

1. Buka `api_v2/src/prompts.py`.
2. Temukan konstanta prompt sesuai disiplin:
   - `CAD_SYSTEM_PROMPT` — Analisis vektor DWG/DXF.
   - `PDF_SYSTEM_PROMPT` — Scan set gambar DED multi-halaman.
   - `IMAGE_SYSTEM_PROMPT` — Cetak biru arsitektur gambar.
   - `BIM_SYSTEM_PROMPT` — Model 3D OpenBIM IFC / Revit.
3. Tambahkan atau ubah aturan (contoh: aturan rasio pembesian, action verb AHSP baru).
4. Simpan file. Perubahan langsung berlaku di request API berikutnya **tanpa mengubah logika eksekusi LLM**.

### Referensi aturan penting yang sudah ada:

| Aturan | Lokasi |
|---|---|
| Konversi unit mm/cm ke meter | `CAD_SYSTEM_PROMPT`, `PDF_SYSTEM_PROMPT` |
| Volume wajib > 0.0 | Semua prompt |
| Prefix AHSP action verb standar | Semua prompt |
| Audit kelengkapan lembar (`coverage_audit`) | `PDF_SYSTEM_PROMPT` |
| Two-Tier BIM Quantity System | `BIM_SYSTEM_PROMPT` |

---

## 4. Konvensi Kode & Maintenance

- **Pydantic Validation**: Semua model API ada di `src/schemas.py`. Jangan membuat dict ad-hoc untuk response; selalu parse melalui `DynamicTakeoffResponse`.
- **Batas Panjang File**: Pertahankan modul di `api_v2/src/` fokus di bawah **500 baris**. Jika modul mulai besar, refactor helper ke sub-modul baru.
- **Dokumentasi Modul**: Setiap perubahan signifikan pada modul `src/` diikuti dengan update file yang sesuai di `docs/modules/`.
- **Frontend State**: Pastikan komponen di `frontend/src/pages/` menggunakan defensive fallback untuk array `ahsp_candidates` agar mock data dan live API response dapat dirender dengan mulus.
- **Debug Logs**: File `debug_logs/latest_raw_pipeline.json` di-overwrite setiap kali ada request baru — gunakan untuk inspeksi pipeline mapping AHSP.

---

## 5. Troubleshooting Umum

| Masalah | Kemungkinan Penyebab | Solusi |
|---|---|---|
| DWG timeout saat parsing | File AutoCAD 2018+ (format AC1032) | Simpan ulang DWG ke format AutoCAD 2013/2010 |
| `RuntimeError: Failed to obtain response` | Semua fallback API gagal | Cek `GEMINI_API_KEY` di `.env`, atau pastikan Primary API aktif |
| AHSP tidak muncul di response | AHSP engine belum terinisialisasi | Cek log startup server, pastikan `ahsp_vectordb/` tidak kosong |
| Frontend tidak bisa reach backend | CORS atau port tidak match | Cek `VITE_API_BASE_URL` di `frontend/.env` dan port CI4 |
| Revit `.rvt` gagal diproses | APS tidak dikonfigurasi | Isi `APS_CLIENT_ID` dan `APS_CLIENT_SECRET` di `api_v2/.env` |
