# 📖 Dokumentasi Final & Keseluruhan REST API Backend — Estimator AI

Dokumentasi ini adalah acuan resmi dan menyeluruh (*single source of truth*) untuk seluruh endpoint REST API backend **CodeIgniter 4** (`http://localhost:8080`) serta integrasi ke **Python FastAPI AI Service** (`http://localhost:8200`).

---

## 🌐 Informasi Server & Header

- **Base URL Backend (CodeIgniter 4)**: `http://localhost:8080`
- **Base URL AI Service (Python FastAPI)**: `http://localhost:8200`
- **Header Request Standar**: `Content-Type: application/json`, `Accept: application/json`
- **CORS**: Aktif untuk semua origin
- **Identifikasi Proyek**: Mendukung pencarian via `id` (integer) maupun `uuid` (v4 string).

---

## 📌 Ringkasan Seluruh Endpoint API Backend

| No | Kategori | Method | Endpoint | Fungsi & Kegunaan |
|:---:|:---|:---:|:---|:---|
| 1 | **Proyek** | `GET` | `/api/projects` | Ambil semua proyek (termasuk foto, lokasi, jasa kontraktor, PPN, & total anggaran). |
| 2 | **Proyek** | `POST` | `/api/projects` | Buat proyek baru dengan data rinci. |
| 3 | **Proyek** | `GET` | `/api/projects/{id_or_uuid}` | Ambil detail 1 proyek + daftar dokumen + riwayat estimasi. |
| 4 | **Proyek** | `PUT` / `PATCH` | `/api/projects/{id_or_uuid}` | Update data proyek (judul, klien, lokasi, jasa kontraktor %, PPN %, foto, status, keterangan). |
| 5 | **Proyek** | `DELETE` | `/api/projects/{id_or_uuid}` | Hapus proyek beserta dokumen & seluruh data estimasi WBS (*Cascade*). |
| 6 | **Estimasi WBS** | `POST` | `/api/projects/{id_or_uuid}/save-estimation` | Simpan JSON hasil AI ke 5 tabel database MySQL. |
| 7 | **Estimasi WBS** | `GET` | `/api/projects/{id_or_uuid}/latest-estimation` | Ambil struktur data WBS proyek terakhir untuk tabel UI frontend. |
| 8 | **Estimasi WBS** | `GET` | `/api/estimation-runs/{run_id_or_uuid}` | Ambil data estimasi berdasarkan ID run tertentu. |
| 9 | **Item WBS** | `PUT` / `PATCH` | `/api/estimation-items/{item_id_or_uuid}` | Update volume, harga satuan, atau kode AHSP per baris item. |
| 10 | **Item WBS** | `DELETE` | `/api/estimation-items/{item_id_or_uuid}` | Hapus 1 baris item pekerjaan WBS. |
| 11 | **AI Analysis** | `POST` | `/api/rab/analyze` | Proxy analisis estimasi WBS dari payload JSON ke Python AI. |
| 12 | **AI Analysis** | `POST` | `/api/rab/analyze-image` | Proxy analisis berkas DED/BIM (PDF, DWG, IFC, RVT) ke Python AI. |
| 13 | **AI Analysis** | `POST` | `/api/rab/analyze-prompt` | Proxy estimasi dari deskripsi konsep teks ke Python AI. |

---

## 🗄️ Skema Basis Data MySQL (CodeIgniter 4)

### 1. Tabel `projects`
| Field | Tipe Data | Keterangan |
|:---|:---|:---|
| `id` | `BIGINT(20) UNSIGNED` | Primary Key (Auto Increment) |
| `uuid` | `CHAR(36)` | Unique UUID v4 |
| `title` | `VARCHAR(255)` | Nama / Judul Proyek *(wajib)* |
| `client` | `VARCHAR(255)` | Pemilik Proyek / Klien |
| `location` | `VARCHAR(255)` | Lokasi Proyek |
| `contractor_fee` | `DECIMAL(5,2)` | Persentase Jasa Kontraktor (Default `10.00`) |
| `ppn` | `DECIMAL(5,2)` | Persentase PPN (Default `11.00`) |
| `status` | `VARCHAR(100)` | Status Proyek (Default `'Perencanaan'`) |
| `summary` | `TEXT` | Keterangan Lain / Ringkasan Proyek |
| `image` | `VARCHAR(255)` | Path / URL Foto Proyek |
| `created_at` | `DATETIME` | Waktu Pembuatan |
| `updated_at` | `DATETIME` | Waktu Perubahan Terakhir |

### 2. Tabel `project_documents`
| Field | Tipe Data | Keterangan |
|:---|:---|:---|
| `id` | `BIGINT(20) UNSIGNED` | Primary Key (Auto Increment) |
| `project_id` | `BIGINT(20) UNSIGNED` | Foreign Key (`projects.id` ON DELETE CASCADE) |
| `file_name` | `VARCHAR(255)` | Nama Berkas Dokumen |
| `file_path` | `VARCHAR(255)` | Path Berkas di Server |
| `file_size` | `BIGINT(20)` | Ukuran Berkas (Byte) |
| `file_type` | `VARCHAR(100)` | Extension / MIME Type Berkas |
| `created_at` | `DATETIME` | Waktu Unggah |
| `updated_at` | `DATETIME` | Waktu Perubahan |

### 3. Tabel `estimation_runs`
- `id`, `uuid`, `project_id`, `project_uuid`, `run_timestamp`, `total_items`, `mapped_high`, `mapped_medium`, `unmapped`, `high_ratio`, `engine_stats`, `created_at`.

### 4. Tabel `wbs_sections`
- `id`, `uuid`, `run_id`, `run_uuid`, `section_id_code`, `code`, `name`, `sort_order`, `created_at`.

### 5. Tabel `estimation_items`
- `id`, `uuid`, `section_id`, `section_uuid`, `item_uid`, `item_no`, `item_code`, `item_name`, `volume`, `unit`, `confidence`, `warning_note`, `ahsp_code`, `ahsp_name`, `ahsp_unit`, `ahsp_score`, `ahsp_status`, `unit_price`, `pipeline_debug_log`, `created_at`.

### 6. Tabel `item_ahsp_candidates`
- `id`, `item_id`, `item_uuid`, `rank`, `id_pekerjaan`, `nama_pekerjaan`, `satuan`, `score`, `base_score`, `reranker`, `created_at`.

---

## 📋 Detail Endpoint API & Contoh Payload Response

### 1. Modul Proyek (`/api/projects`)

#### 1.1 `GET /api/projects`
**Response (200 OK):**
```json
{
  "status": 200,
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Pembangunan Ruko 3 Lantai",
      "client": "PT Maju Jaya",
      "location": "Jakarta Selatan, DKI Jakarta",
      "contractor_fee": 10.00,
      "ppn": 11.00,
      "status": "Perencanaan",
      "summary": "Proyek pembangunan ruko 3 lantai area commercial.",
      "image": "/uploads/projects/ruko_3_lantai.png",
      "total_budget": 350000000,
      "latest_run": {
        "id": 1,
        "uuid": "770e8400-e29b-41d4-a716-446655440099",
        "run_timestamp": "2026-09-07 14:30:00",
        "total_items": 12,
        "mapped_high": 10,
        "mapped_medium": 1,
        "unmapped": 1,
        "high_ratio": 83.33
      },
      "created_at": "2026-09-07 14:00:00",
      "updated_at": "2026-09-07 14:30:00"
    }
  ]
}
```

#### 1.2 `POST /api/projects`
**Request Body:**
```json
{
  "title": "Pembangunan Gudang Logistik",
  "client": "PT Trans Cargo",
  "location": "Cikarang, Jawa Barat",
  "contractor_fee": 10.00,
  "ppn": 11.00,
  "status": "Perencanaan",
  "summary": "Konstruksi gudang struktur baja dan lantai beton k-300.",
  "image": "/uploads/projects/gudang.jpg"
}
```

**Response (201 Created):**
```json
{
  "status": 201,
  "success": true,
  "message": "Proyek berhasil dibuat.",
  "data": {
    "id": 2,
    "uuid": "880e8400-e29b-41d4-a716-446655440022",
    "title": "Pembangunan Gudang Logistik",
    "client": "PT Trans Cargo",
    "location": "Cikarang, Jawa Barat",
    "contractor_fee": 10.00,
    "ppn": 11.00,
    "status": "Perencanaan",
    "summary": "Konstruksi gudang struktur baja dan lantai beton k-300.",
    "image": "/uploads/projects/gudang.jpg",
    "created_at": "2026-09-10 08:30:00",
    "updated_at": "2026-09-10 08:30:00"
  }
}
```

#### 1.3 `GET /api/projects/{id_or_uuid}`
**Response (200 OK):**
```json
{
  "status": 200,
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Pembangunan Ruko 3 Lantai",
    "client": "PT Maju Jaya",
    "location": "Jakarta Selatan",
    "contractor_fee": 10.00,
    "ppn": 11.00,
    "status": "Perencanaan",
    "summary": "Pembangunan ruko 3 lantai.",
    "image": "/uploads/projects/ruko.jpg",
    "documents": [
      {
        "id": 1,
        "project_id": 1,
        "file_name": "DED_Struktur_Ruko.ifc",
        "file_path": "/uploads/documents/DED_Struktur_Ruko.ifc",
        "file_size": 24500000,
        "file_type": "application/x-ifc",
        "created_at": "2026-09-07 14:05:00"
      }
    ],
    "estimation_runs": [
      {
        "id": 1,
        "uuid": "770e8400-e29b-41d4-a716-446655440099",
        "run_timestamp": "2026-09-07 14:30:00",
        "total_items": 12
      }
    ]
  }
}
```

#### 1.4 `PUT /api/projects/{id_or_uuid}`
**Request Body:**
```json
{
  "title": "Pembangunan Gudang Logistik (Revisi 1)",
  "contractor_fee": 12.00,
  "ppn": 11.00,
  "status": "Tahap Estimasi"
}
```

#### 1.5 `DELETE /api/projects/{id_or_uuid}`
**Response (200 OK):**
```json
{
  "status": 200,
  "success": true,
  "message": "Proyek berhasil dihapus beserta seluruh riwayat estimasinya."
}
```

---

### 2. Modul Estimasi WBS (`/api/projects/{id}/...`)

#### 2.1 `POST /api/projects/{id_or_uuid}/save-estimation`
Menyimpan hasil analisis AI lengkap ke database.

**Request Body (Format Output AI):**
```json
{
  "summary_metrics": {
    "total_items": 2,
    "mapped_high": 1,
    "mapped_medium": 0,
    "unmapped": 1,
    "high_ratio": 50.0
  },
  "sections": [
    {
      "id": "sec-A",
      "code": "A",
      "name": "PEKERJAAN PERSIAPAN & TANAH",
      "items": [
        {
          "id": "item-A-1",
          "no": 1,
          "code": "A.1",
          "name": "Pembersihan Lapangan dan Perataan",
          "volume": 120.5,
          "unit": "m2",
          "confidence": "high",
          "warning_note": null,
          "unit_price": 15500,
          "ahsp_mapping": {
            "ahsp_code": "4.2.1.1",
            "ahsp_name": "1 m2 Membersihkan lapangan dan perataan",
            "ahsp_unit": "m2",
            "ahsp_score": 0.952,
            "ahsp_status": "mapped_high",
            "candidates": [
              {
                "rank": 1,
                "id_pekerjaan": "4.2.1.1",
                "nama_pekerjaan": "1 m2 Membersihkan lapangan dan perataan",
                "satuan": "m2",
                "score": 0.952,
                "base_score": 0.910,
                "reranker": "bge_m3_hybrid"
              }
            ]
          }
        }
      ]
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "status": 201,
  "success": true,
  "message": "Estimasi dan pemetaan AHSP berhasil disimpan ke database.",
  "data": {
    "project_id": 1,
    "project_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "run_id": 1,
    "run_uuid": "770e8400-e29b-41d4-a716-446655440099"
  }
}
```

#### 2.2 `GET /api/projects/{id_or_uuid}/latest-estimation`
Mengambil data WBS estimasi terakhir proyek dalam bentuk JSON terstruktur untuk UI frontend.

#### 2.3 `PUT /api/estimation-items/{item_id_or_uuid}`
Memperbarui volume, harga satuan, atau kode AHSP item WBS tersimpan.

**Request Body:**
```json
{
  "volume": 150.0,
  "unit_price": 18000,
  "ahsp_code": "4.2.1.1",
  "ahsp_status": "mapped_high"
}
```

#### 2.4 `DELETE /api/estimation-items/{item_id_or_uuid}`
Menghapus 1 baris item WBS dari database.

---

### 3. Modul Analisis AI Proxy (`/api/rab/...`)

#### 3.1 `POST /api/rab/analyze-image`
Diteruskan ke Python AI Service (`/api/rab/analyze-image`).
- **Content-Type**: `multipart/form-data`
- **Form Fields**:
  - `ded_file`: Berkas PDF, DWG, DXF, IFC, RVT, NWD, SKP, PNG, JPG (Maks. 500MB).
  - `name`: Nama Proyek.
  - `client`: Nama Klien.

#### 3.2 `POST /api/rab/analyze-prompt`
Diteruskan ke Python AI Service (`/api/rab/analyze-prompt`).
- **Content-Type**: `application/json`
- **Request Body**:
  ```json
  {
    "name": "Rumah Minimalis 2 Lantai",
    "client": "Bpk. Budi",
    "prompt": "Rumah tinggal 2 lantai luas 120m2 dengan 3 kamar tidur, 2 kamar mandi, struktur beton bertulang, atap baja ringan genteng keramik."
  }
  ```