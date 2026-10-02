# 📖 Dokumentasi Final REST API Database & Estimasi WBS
## Backend CodeIgniter 4 — Estimator AI

Dokumentasi ini adalah panduan integrasi frontend untuk menyimpan, mengambil, dan memperbarui data proyek serta hasil estimasi WBS di database MySQL.

- **Base URL**: `http://localhost:8080`
- **Header Standar**: `Content-Type: application/json`
- **CORS**: Aktif untuk semua request

---

## 📌 Ringkasan Alur Kerja Frontend & Backend

```
[ Frontend React ]
   1. User buat proyek baru      ──> POST /api/projects
   2. AI selesai analisis JSON   ──> POST /api/projects/{id}/save-estimation (Simpan ke 5 tabel DB)
   3. Buka halaman tabel WBS     ──> GET  /api/projects/{id}/latest-estimation (Ambil data tersimpan)
   4. User edit harga/volume     ──> PUT  /api/estimation-items/{id} (Koreksi data)
   5. Hapus proyek / reset       ──> DELETE /api/projects/{id}
```

---

## 📋 Daftar Endpoint

| No | Kategori | Method | Endpoint | Kegunaan |
|:---:|:---|:---:|:---|:---|
| 1 | **Proyek** | `GET` | `/api/projects` | Mengambil semua daftar proyek + total anggaran |
| 2 | **Proyek** | `POST` | `/api/projects` | Membuat proyek baru |
| 3 | **Proyek** | `GET` | `/api/projects/{id}` | Mengambil detail 1 proyek |
| 4 | **Proyek** | `PUT` | `/api/projects/{id}` | Mengupdate data umum proyek (judul, klien, status) |
| 5 | **Proyek** | `DELETE` | `/api/projects/{id}` | Menghapus proyek beserta seluruh data WBS (*Cascade*) |
| 6 | **Estimasi WBS** | `POST` | `/api/projects/{id}/save-estimation` | Menyimpan JSON hasil AI ke database proyek |
| 7 | **Estimasi WBS** | `GET` | `/api/projects/{id}/latest-estimation` | Mengambil data WBS terakhir proyek untuk tabel UI |
| 8 | **Estimasi WBS** | `GET` | `/api/estimation-runs/{run_id}` | Mengambil data estimasi berdasarkan ID run tertentu |
| 9 | **Item WBS** | `PUT` | `/api/estimation-items/{item_id}` | Update volume, harga satuan, atau kode AHSP per baris |
| 10 | **Item WBS** | `DELETE` | `/api/estimation-items/{item_id}` | Hapus 1 baris item pekerjaan |

---

## 1. Modul Proyek (`/api/projects`)

### 1.1 Ambil Semua Proyek
- **Method**: `GET`
- **Endpoint**: `/api/projects`

#### Contoh Response (200 OK):
```json
{
  "status": 200,
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Pembangunan Ruko 3 Lantai",
      "client": "PT Maju Jaya",
      "status": "Perencanaan",
      "summary": "Proyek pembangunan ruko 3 lantai...",
      "total_budget": 350000000,
      "latest_run": {
        "id": 1,
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

---

### 1.2 Buat Proyek Baru
- **Method**: `POST`
- **Endpoint**: `/api/projects`

#### Request Body:
```json
{
  "title": "Renovasi Rumah Tinggal 2 Lantai",
  "client": "Bpk. Hendra",
  "status": "Perencanaan",
  "summary": "Pekerjaan struktur dan arsitektur rumah tinggal."
}
```

#### Response (201 Created):
```json
{
  "status": 201,
  "success": true,
  "message": "Proyek berhasil dibuat.",
  "data": {
    "id": 2,
    "title": "Renovasi Rumah Tinggal 2 Lantai",
    "client": "Bpk. Hendra",
    "status": "Perencanaan",
    "summary": "Pekerjaan struktur dan arsitektur rumah tinggal.",
    "created_at": "2026-09-07 15:00:00",
    "updated_at": "2026-09-07 15:00:00"
  }
}
```

---

### 1.3 Update Metadata Proyek
- **Method**: `PUT`
- **Endpoint**: `/api/projects/{id}`

#### Request Body:
```json
{
  "title": "Renovasi Rumah Tinggal 2 Lantai (Revisi)",
  "status": "Tahap Estimasi",
  "summary": "Penambahan pekerjaan kanopi dan interior."
}
```

---

### 1.4 Hapus Proyek
- **Method**: `DELETE`
- **Endpoint**: `/api/projects/{id}`

> [!NOTE]
> Menghapus proyek akan otomatis menghapus seluruh tabel WBS, item, dan kandidat AHSP yang berelasi dengan proyek ini (*Cascade Delete*).

---

## 2. Modul Estimasi WBS & AHSP (`/api/projects/{id}/...`)

### 2.1 Simpan Full JSON Hasil AI ke Database
Frontend mengirimkan JSON hasil AI ke endpoint ini untuk disimpan ke 5 tabel database.

- **Method**: `POST`
- **Endpoint**: `/api/projects/{id}/save-estimation`

#### Request Body (Format Hasil AI):
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
        },
        {
          "id": "item-A-2",
          "no": 2,
          "code": "A.2",
          "name": "Pemasangan Ornamen Akrilik Custom",
          "volume": 10.0,
          "unit": "m2",
          "confidence": "medium",
          "warning_note": "Item tidak ditemukan di basis data AHSP.",
          "unit_price": 350000,
          "ahsp_mapping": {
            "ahsp_code": "BARU-001",
            "ahsp_name": "Pemasangan Ornamen Akrilik Custom",
            "ahsp_unit": "m2",
            "ahsp_score": 0.0,
            "ahsp_status": "unmapped",
            "candidates": []
          }
        }
      ]
    }
  ]
}
```

#### Response (201 Created):
```json
{
  "status": 201,
  "success": true,
  "message": "Estimasi dan pemetaan AHSP berhasil disimpan ke database.",
  "data": {
    "project_id": 1,
    "run_id": 1
  }
}
```

---

### 2.2 Ambil Data WBS Terakhir Proyek (Untuk Render UI)
Mengambil data tersimpan dan menyusunnya kembali menjadi struktur JSON lengkap untuk tabel WBS frontend.

- **Method**: `GET`
- **Endpoint**: `/api/projects/{id}/latest-estimation`

#### Response (200 OK):
```json
{
  "status": 200,
  "success": true,
  "data": {
    "run_id": 1,
    "project_id": 1,
    "project_title": "Pembangunan Ruko 3 Lantai",
    "project_client": "PT Maju Jaya",
    "run_timestamp": "2026-09-07 14:30:00",
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
        "db_id": 1,
        "code": "A",
        "name": "PEKERJAAN PERSIAPAN & TANAH",
        "sort_order": 1,
        "items": [
          {
            "id": "item-A-1",
            "db_id": 1,
            "no": 1,
            "code": "A.1",
            "name": "Pembersihan Lapangan dan Perataan",
            "volume": 120.5,
            "unit": "m2",
            "confidence": "high",
            "warning_note": null,
            "unit_price": 15500,
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
                "base_score": 0.91,
                "reranker": "bge_m3_hybrid"
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

### 2.3 Edit Baris Item Pekerjaan (Koreksi Estimator)
Digunakan saat user mengedit volume, harga satuan, atau mengubah pilihan kode AHSP di tabel.

- **Method**: `PUT`
- **Endpoint**: `/api/estimation-items/{db_id}`

#### Request Body (Hanya kirim field yang diedit):
```json
{
  "volume": 150.0,
  "unit_price": 18000,
  "ahsp_code": "4.2.1.1",
  "ahsp_status": "mapped_high"
}
```

#### Response (200 OK):
```json
{
  "status": 200,
  "success": true,
  "message": "Item estimasi berhasil diperbarui.",
  "data": {
    "id": "1",
    "volume": "150.0000",
    "unit_price": "18000.00",
    "ahsp_code": "4.2.1.1",
    "ahsp_status": "mapped_high"
  }
}
```

---

### 2.4 Hapus Baris Item Pekerjaan
- **Method**: `DELETE`
- **Endpoint**: `/api/estimation-items/{db_id}`

#### Response (200 OK):
```json
{
  "status": 200,
  "success": true,
  "message": "Item estimasi berhasil dihapus."
}
```
