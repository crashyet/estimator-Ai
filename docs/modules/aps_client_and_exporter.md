# `src/aps_client.py` — Autodesk Platform Services (APS) Client

Enkapsulasi seluruh komunikasi dengan Autodesk Platform Services Cloud API untuk konversi dan ekstraksi kuantitas dari model Revit (`.rvt`) dan Navisworks (`.nwd`, `.nwc`) native.

---

## Kelas: `APSConverter`

### Alur Konversi

```
APSConverter.convert_autodesk_model(file_path, output_ifc_path, client_id, client_secret)
        │
        ▼
Step 1: OAuth 2.0 Token (client_credentials grant)
        │
        ▼
Step 2: Create/Verify OSS Bucket (policyKey: transient)
        │
        ▼
Step 3: Upload File ke S3 (Signed S3 Upload URL)
        ├── 3a. GET signed URL
        ├── 3b. PUT file bytes ke AWS S3
        └── 3c. POST finalize upload → dapat objectId → encode ke URN base64
        │
        ▼
Step 4: Trigger Translation Job
        ├── Untuk .rvt: Coba IFC langsung → jika gagal, fallback ke SVF2
        └── Untuk .nwd/.nwc: SVF2 langsung
        │
        ▼
Step 5: Poll Manifest (setiap 5 detik, maks 300 detik)
        │
        ├── Status "success" → extract_quantities_from_aps_properties()
        └── Status "failed" → raise ValueError
```

---

## Metode

### `convert_autodesk_model(file_path, output_ifc_path, client_id, client_secret)` ⭐
Entry point utama untuk konversi model Autodesk.

**Returns**: 
- `True` jika konversi ke IFC berhasil (file ditulis ke `output_ifc_path`)
- `List[BIMElementQuantity]` jika kuantitas diekstrak via APS Properties API

### `extract_quantities_from_aps_properties(urn_b64, headers)`
Mengekstrak kuantitas elemen dari Autodesk Model Derivative Properties API.

**Handling HTTP 413 (Payload Too Large)**:
Jika model terlalu besar untuk `/properties` endpoint, fallback ke `/objecttree` hierarchy untuk mengekstrak nama elemen dan mengklasifikasikannya secara heuristik.

**Klasifikasi otomatis dari nama elemen** (jika `/objecttree` fallback):
- "wall" / "dinding" → `Walls`
- "column" / "kolom" → `Columns`
- "beam" / "balok" → `Beams`
- dst.

### `convert_rvt_to_ifc(rvt_file_path, output_ifc_path, client_id, client_secret)` 
Shorthand untuk konversi `.rvt` → `.ifc` yang mengembalikan `bool`.

---

## Konfigurasi yang Diperlukan (`.env`)

```env
APS_CLIENT_ID=your-autodesk-client-id
APS_CLIENT_SECRET=your-autodesk-client-secret
RVT_TIMEOUT_SECONDS=300
```

---
---

# `src/exporter.py` — Export Engine

Mengekspor hasil `DynamicTakeoffResponse` ke format file eksternal.

---

## Fungsi

### `export_takeoff_to_excel(takeoff_data, output_path)`
Mengekspor ke Excel (`.xlsx`) dengan 2 sheet:

| Sheet | Konten |
|---|---|
| `RAB Volume Takeoff` | Semua item pekerjaan (kode seksi, nama, volume, satuan, catatan AI) |
| `Project Info` | Metadata proyek (judul, klien, status, ringkasan, total seksi & item) |

```python
from src.exporter import export_takeoff_to_excel
path = export_takeoff_to_excel(takeoff_result, "/tmp/rab.xlsx")
```

### `export_takeoff_to_json(takeoff_data, output_path)`
Mengekspor ke JSON (`.json`) menggunakan format flat `to_frontend_format()` — kompatibel langsung dengan React frontend.

```python
from src.exporter import export_takeoff_to_json
path = export_takeoff_to_json(takeoff_result, "/tmp/rab.json")
```
