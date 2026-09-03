# Referensi API V2

**Estimator API V2** menyediakan REST endpoints untuk AI-driven construction takeoff dan AHSP semantic matching.

> **Catatan Arsitektur**: Frontend (port 5173) berkomunikasi ke **Backend CI4** (port 8080), yang kemudian mem-proxy request ke **API V2 Python** ini (port 8200). Developer dapat memanggil API V2 langsung untuk testing via Swagger.

- **Base URL**: `http://localhost:8200/api/v2`
- **Swagger Interactive UI**: `http://localhost:8200/docs`
- **ReDoc UI**: `http://localhost:8200/redoc`

---

## 1. Backend CI4 Gateway Endpoints (`backend/`)

Ini adalah endpoint yang dipanggil oleh frontend. CI4 meneruskan request ke API V2.

### `POST /api/rab/analyze`
- **Deskripsi**: Menerima upload file gambar konstruksi dari frontend, meneruskan ke `/api/v2/takeoff/*` yang sesuai.
- **Content-Type**: `multipart/form-data`
- **Form Parameters**:
  - `ded_file` (*UploadFile*, required): File gambar (`.dwg`, `.dxf`, `.pdf`, `.jpg`, `.ifc`, dll.)
  - `name` (*string*, required): Nama proyek
  - `client` (*string*, optional): Nama klien

---

## 2. Takeoff Endpoints (`routers/takeoff.py`)

### 2.1 `POST /api/v2/takeoff/cad`
Analisis AI material takeoff pada file vektor CAD (`.dwg`, `.dxf`, `.svg`, `.plt`).

- **Content-Type**: `multipart/form-data`
- **Form Parameters**:
  - `file` (*UploadFile*, required): File CAD
  - `project_name` (*string*, optional, default: `"Proyek CAD DWG"`): Judul proyek
  - `client_name` (*string*, optional, default: `"Client"`): Nama klien

**Contoh Request (cURL langsung ke API V2)**:
```bash
curl -X POST "http://localhost:8200/api/v2/takeoff/cad" \
  -F "file=@denah_rumah.dwg" \
  -F "project_name=Rumah Tinggal Type 45" \
  -F "client_name=Bpk Heri"
```

---

### 2.2 `POST /api/v2/takeoff/pdf`
Analisis multimodal page-by-page AI takeoff pada set gambar PDF.

- **Content-Type**: `multipart/form-data`
- **Form Parameters**:
  - `file` (*UploadFile*, required): File PDF multi-halaman
  - `project_name` (*string*, optional): Judul proyek
  - `client_name` (*string*, optional): Nama klien

---

### 2.3 `POST /api/v2/takeoff/image`
Analisis AI berbasis vision pada gambar cetak biru (`.jpg`, `.jpeg`, `.png`, `.webp`).

- **Content-Type**: `multipart/form-data`
- **Form Parameters**:
  - `file` (*UploadFile*, required): File gambar
  - `project_name` (*string*, optional): Judul proyek
  - `client_name` (*string*, optional): Nama klien

---

### 2.4 `POST /api/v2/takeoff/bim`
Analisis takeoff parametrik 3D BIM pada file OpenBIM (`.ifc`) atau Autodesk Revit (`.rvt`, `.rfa`, `.nwd`, `.nwc`).

- **Content-Type**: `multipart/form-data`
- **Form Parameters**:
  - `file` (*UploadFile*, required): File model 3D BIM
  - `project_name` (*string*, optional): Judul proyek
  - `client_name` (*string*, optional): Nama klien

---

## 3. Response Schema (`DynamicTakeoffResponse`)

Semua endpoint `/takeoff/*` mengembalikan JSON sesuai `DynamicTakeoffResponse`.

**Contoh Response**:
```json
{
  "project": {
    "title": "Rumah Tinggal Type 45",
    "client": "Bpk Heri",
    "status": "Perencanaan"
  },
  "project_summary": "Analisis CAD menghasilkan 4 seksi WBS dengan 18 item pekerjaan.",
  "wbs_sections": [
    {
      "section": {
        "id": "sec-A",
        "type": "section",
        "code": "A",
        "name": "PEKERJAAN PERSIAPAN"
      },
      "items": [
        {
          "id": "item-A-1",
          "type": "item",
          "sectionCode": "A",
          "no": 1,
          "code": "CUSTOM",
          "name": "Pembersihan Lapangan dan Pemataan",
          "volume": 120.0,
          "unit": "m2",
          "confidence": "high",
          "warning_note": "Luas tapak 10m x 12m = 120 m2",
          "ahsp_code": "1.1.1.1",
          "ahsp_name": "Pembersihan Lapangan dan Perataan",
          "ahsp_unit": "m2",
          "ahsp_score": 0.92,
          "ahsp_status": "mapped_high",
          "ahsp_candidates": [
            {
              "id_pekerjaan": "1.1.1.1",
              "nama_pekerjaan": "Pembersihan Lapangan dan Perataan",
              "satuan": "m2",
              "score": 0.92
            }
          ]
        }
      ]
    }
  ]
}
```

**Nilai `ahsp_status`**:
| Status | Arti | Badge Frontend |
|---|---|---|
| `mapped_high` | Score ≥ 0.65, mapping sangat yakin | 🟢 Hijau |
| `mapped_medium` | Score 0.50–0.64, perlu dikonfirmasi | 🟡 Kuning |
| `unmapped` | Tidak ada kandidat yang memadai | 🔴 Abu/Merah |

---

## 4. AHSP Search & Mapping Endpoints (`routers/ahsp.py`)

### 4.1 `GET /api/v2/ahsp/search`
Pencarian item AHSP standar berdasarkan kata kunci.

- **Query Parameters**:
  - `q` (*string*, required): Kata kunci pencarian (contoh: `plesteran`)
  - `limit` (*int*, optional, default: `10`): Maksimum hasil

**Contoh**:
```bash
curl "http://localhost:8200/api/v2/ahsp/search?q=plesteran&limit=5"
```

### 4.2 `POST /api/v2/ahsp/map-item`
Memetakan satu item pekerjaan kustom ke AHSP Vector Database.

- **Request Body**:
```json
{
  "name": "Plesteran Dinding 1:4",
  "unit": "m2"
}
```

---

## 5. Referensi Environment Variables

### `api_v2/.env`

| Variabel | Wajib | Default | Keterangan |
|---|:---:|:---:|---|
| `GEMINI_API_KEY` | ✅ | `""` | API Key Google Gemini Generative AI SDK |
| `GEMINI_MODEL` | ❌ | `"gemini-2.5-flash"` | Model Gemini utama |
| `PRIMARY_API_BASE` | ❌ | `""` | Endpoint proxy API OpenAI-compatible opsional |
| `PRIMARY_API_KEY` | ❌ | `""` | API Key untuk primary proxy |
| `PRIMARY_MODEL` | ❌ | `""` | Model name untuk primary proxy |
| `APS_CLIENT_ID` | ❌ | `""` | Autodesk Platform Services Client ID (untuk konversi `.rvt`) |
| `APS_CLIENT_SECRET` | ❌ | `""` | Autodesk Platform Services Client Secret |
| `RVT_TIMEOUT_SECONDS` | ❌ | `300` | Timeout (detik) untuk job konversi Revit |
| `HOST` | ❌ | `0.0.0.0` | Host server FastAPI |
| `PORT` | ❌ | `8200` | Port server FastAPI |
| `MAX_UPLOAD_SIZE_MB` | ❌ | `500` | Batas maksimum ukuran file upload (MB) |

### `backend/.env`

| Variabel | Default | Keterangan |
|---|:---:|---|
| `PYTHON_API_URL` | `http://localhost:8200` | URL AI Engine Python yang di-proxy oleh CI4 |
| `app.baseURL` | `http://localhost:8080/` | URL base backend CI4 |
| `CI_ENVIRONMENT` | `development` | Mode environment CI4 |
