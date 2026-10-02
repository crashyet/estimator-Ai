# Dokumentasi REST API CodeIgniter 4 - Estimator AI

Dokumentasi resmi untuk seluruh endpoint API yang dilayani oleh backend **CodeIgniter 4**. Backend ini berfungsi sebagai:
1. **Penyimpanan & Manajemen Proyek (CRUD)**: Menyimpan master proyek, sesi estimasi (estimation runs), breakdown pekerjaan (WBS sections & items), serta riwayat pemetaan AHSP.
2. **Reverse Proxy & Orkesstrator AI (FastAPI)**: Menjembatani request analisis file DED/BIM/CAD/Gambar maupun prompt teks ke microservice Python AI (FastAPI).
3. **Penyedia Data Master AHSP**: Proxy pencarian dan katalog master data analisis harga satuan pekerjaan.

---

## 📌 Informasi Dasar

- **Base URL (Lokal)**: `http://localhost:80` atau `http://127.0.0.1:80` (Port 80/8080 via Nginx/Apache)
- **Base URL (Tunnel / Domain)**: `https://esti.eyi.my.id`
- **Default Format**: `application/json`
- **Upload Format**: `multipart/form-data` (Maksimal 500 MB)
- **Identifier**: Semua endpoint proyek & item mendukung parameter berupa **Integer ID** (`1`, `2`) maupun **UUID v4** (`550e8400-e29b-41d4-a716-446655440000`).

---

## 📑 Daftar Grup Endpoint

1. [Modul AI Analysis & Estimasi (RABController)](#1-modul-ai-analysis--estimasi)
2. [Modul Manajemen Proyek (ProjectController)](#2-modul-manajemen-proyek)
3. [Modul Riwayat & Item Estimasi (EstimationController)](#3-modul-riwayat--item-estimasi)
4. [Modul Katalog & Pemetaan AHSP (AHSPController)](#4-modul-katalog--pemetaan-ahsp)

---

## 1. Modul AI Analysis & Estimasi

Endpoint ini menghubungkan web frontend ke microservice Python AI (`localhost:8200`).

### 1.1 Analisis Dokumen DED / Gambar / CAD / BIM
Menganalisis berkas teknis konstruksi untuk mengekstraksi WBS (Work Breakdown Structure), volume, satuan, dan pemetaan awal AHSP.

- **Method**: `POST`
- **Endpoint**: `/api/rab/analyze` atau `/api/rab/analyze-image`
- **Content-Type**: `multipart/form-data`
- **Format File yang Didukung**:
  - **BIM 3D**: `.rvt` (Revit), `.ifc` (OpenBIM), `.skp` (SketchUp), `.nwd`, `.nwc` (Navisworks)
  - **CAD**: `.dwg`, `.dxf`, `.dwt`, `.dwf`, `.dwfx`, `.svg`, `.plt`
  - **Dokumen / Gambar**: `.pdf`, `.png`, `.jpg`, `.jpeg`
- **Ukuran Maksimal**: 500 MB

#### Request Parameters (Form Data):
| Parameter | Tipe | Wajib | Keterangan |
| :--- | :--- | :---: | :--- |
| `ded_file` atau `file` | Binary | Ya | Berkas teknis yang akan dianalisis |
| `name` | String | Tidak | Nama proyek (Default: `Proyek DED`) |
| `client` | String | Tidak | Nama klien / pemilik (Default: `Client`) |
| `project_id` | String | Tidak | ID atau UUID proyek jika sudah dibuat sebelumnya |

#### Response (`200 OK`):
```json
{
  "success": true,
  "project": {
    "title": "Kos-Kosan 2 Lantai 10 Kamar",
    "client": "Bpk. Hendra",
    "budget": 850000000,
    "status": "Perencanaan"
  },
  "anggaran": [
    {
      "id": "sec-A",
      "type": "section",
      "code": "A",
      "name": "PEKERJAAN PERSIAPAN"
    },
    {
      "id": "item-A-0",
      "type": "item",
      "sectionCode": "A",
      "no": 1,
      "name": "Pembersihan Lapangan dan Perataan",
      "volume": 120.0,
      "unit": "m2",
      "unitPrice": 15000,
      "ahsp_status": "mapped_high",
      "ahsp_code": "A.2.2.1.1",
      "ahsp_name": "Pembersihan 1 m2 lapangan dan perataan"
    }
  ]
}
```

---

### 1.2 Analisis Konsep Desain Rumah dari Teks (Prompt)
Membuat RAB lengkap dan spesifikasi volume berdasarkan deskripsi teks bebas (AI Generative Estimator).

- **Method**: `POST`
- **Endpoint**: `/api/rab/analyze-prompt`
- **Content-Type**: `application/json`

#### Request Body (JSON):
```json
{
  "name": "Desain Villa Modern 2 Lantai",
  "client": "Ibu Ratna",
  "prompt": "Rumah 2 lantai ukuran 8x15 meter dengan 3 kamar tidur, 2 kamar mandi, kolam renang mini di belakang, dan struktur beton bertulang."
}
```

#### Response (`200 OK`):
Struktur JSON sama dengan hasil endpoint analisis dokumen DED di atas.

---

## 2. Modul Manajemen Proyek

Endpoint untuk operasi CRUD master data proyek.

### 2.1 Daftar Semua Proyek
- **Method**: `GET`
- **Endpoint**: `/api/projects`
- **Response (`200 OK`)**:
```json
{
  "status": 200,
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "8cb67c7e-e17f-4ea6-8aa2-d7b3cf68ea4c",
      "title": "Pembangunan Rumah Tinggal 2 Lantai",
      "client": "PT Bangun Graha",
      "location": "Jakarta Selatan",
      "contractor_fee": 10.00,
      "ppn": 11.00,
      "status": "Perencanaan",
      "summary": "Estimasi awal pembangunan rumah tinggal.",
      "image": null,
      "total_budget": 450000000.00,
      "latest_run": {
        "id": 5,
        "uuid": "21dcaee0-77a8-4444-a039-166fbbf61502",
        "total_items": 35,
        "mapped_high": 28,
        "mapped_medium": 5,
        "unmapped": 2,
        "high_ratio": 0.8
      },
      "created_at": "2026-09-15 10:00:00",
      "updated_at": "2026-09-17 08:30:00"
    }
  ]
}
```

---

### 2.2 Membuat Proyek Baru
- **Method**: `POST`
- **Endpoint**: `/api/projects`
- **Content-Type**: `application/json`

#### Request Body:
```json
{
  "title": "Rumah Minimalis Modern",
  "client": "Bpk. Budi Santoso",
  "location": "Bandung, Jawa Barat",
  "contractor_fee": 10.0,
  "ppn": 11.0,
  "status": "Perencanaan",
  "summary": "Proyek pembangunan rumah 2 lantai tipe 120"
}
```

#### Response (`201 Created`):
```json
{
  "status": 201,
  "success": true,
  "message": "Proyek berhasil dibuat.",
  "data": {
    "id": 2,
    "uuid": "b0a2cfbf-829d-400f-8c38-4e565ad0c0b1",
    "title": "Rumah Minimalis Modern",
    "client": "Bpk. Budi Santoso",
    "location": "Bandung, Jawa Barat",
    "contractor_fee": 10.0,
    "ppn": 11.0,
    "status": "Perencanaan",
    "created_at": "2026-09-17 14:00:00"
  }
}
```

---

### 2.3 Detail Proyek
Mengambil detail satu proyek beserta daftar riwayat dokumen dan estimasi run.

- **Method**: `GET`
- **Endpoint**: `/api/projects/{id_or_uuid}`
- **Parameter URL**:
  - `{id_or_uuid}`: Integer ID (contoh: `1`) atau UUID (contoh: `b0a2cfbf-829d-400f-8c38-4e565ad0c0b1`)
- **Response (`200 OK`)**:
```json
{
  "status": 200,
  "success": true,
  "data": {
    "id": 1,
    "uuid": "8cb67c7e-e17f-4ea6-8aa2-d7b3cf68ea4c",
    "title": "Pembangunan Rumah Tinggal 2 Lantai",
    "client": "PT Bangun Graha",
    "location": "Jakarta Selatan",
    "contractor_fee": 10.0,
    "ppn": 11.0,
    "status": "Perencanaan",
    "documents": [],
    "estimation_runs": [
      {
        "id": 5,
        "uuid": "21dcaee0-77a8-4444-a039-166fbbf61502",
        "total_items": 35,
        "run_timestamp": "2026-09-17 11:20:00"
      }
    ]
  }
}
```

---

### 2.4 Update Metadata Proyek
- **Method**: `PUT` atau `PATCH`
- **Endpoint**: `/api/projects/{id_or_uuid}`
- **Request Body (JSON)**:
```json
{
  "title": "Rumah Minimalis Modern - Revisi 1",
  "contractor_fee": 12.5,
  "status": "Dalam Pengerjaan"
}
```
- **Response (`200 OK`)**: Mengembalikan data proyek terbaru yang telah di-update.

---

### 2.5 Hapus Proyek
Menghapus proyek beserta relasi dokumen, runs, sections, items, dan mapping AHSP.

- **Method**: `DELETE`
- **Endpoint**: `/api/projects/{id_or_uuid}`
- **Response (`200 OK`)**:
```json
{
  "status": 200,
  "success": true,
  "message": "Proyek berhasil dihapus beserta seluruh riwayat estimasinya."
}
```

---

## 3. Modul Riwayat & Item Estimasi

Endpoint untuk menyimpan hasil estimasi AI ke database CI4 dan memanipulasi detail item pekerjaan.

### 3.1 Simpan Hasil Estimasi AI
Menyimpan WBS sections, item volume, harga satuan, dan kandidat AHSP yang dihasilkan AI ke dalam database relasional.

- **Method**: `POST`
- **Endpoint**: `/api/projects/{id_or_uuid}/save-estimation`
- **Content-Type**: `application/json`

#### Request Body:
Menerima payload langsung dari response AI (`sections`, `wbs_sections`, atau flat `items`):
```json
{
  "sections": [
    {
      "code": "A",
      "name": "PEKERJAAN PERSIAPAN",
      "items": [
        {
          "item_uid": "item-A-1",
          "name": "Pembersihan Lokasi",
          "volume": 100.0,
          "unit": "m2",
          "ahsp_code": "A.2.2.1.1",
          "ahsp_name": "Pembersihan 1 m2 lapangan",
          "unit_price": 15000,
          "ahsp_status": "mapped_high",
          "ahsp_score": 0.95
        }
      ]
    }
  ]
}
```

#### Response (`200 OK`):
```json
{
  "status": 200,
  "success": true,
  "message": "Estimasi berhasil disimpan ke database.",
  "data": {
    "run_id": 12,
    "run_uuid": "60a7de59-b1d5-450f-a7e8-e5a9eeac860d",
    "project_id": 1,
    "total_items": 1,
    "mapped_high": 1,
    "mapped_medium": 0,
    "unmapped": 0
  }
}
```

---

### 3.2 Ambil Estimasi Terkini Proyek (Latest Run)
Mengambil sesi estimasi terbaru dari suatu proyek dalam format WBS terstruktur.

- **Method**: `GET`
- **Endpoint**: `/api/projects/{id_or_uuid}/latest-estimation`
- **Response (`200 OK`)**:
```json
{
  "status": 200,
  "success": true,
  "data": {
    "run": {
      "id": 12,
      "uuid": "60a7de59-b1d5-450f-a7e8-e5a9eeac860d",
      "total_items": 1
    },
    "sections": [
      {
        "code": "A",
        "name": "PEKERJAAN PERSIAPAN",
        "items": [
          {
            "id": 45,
            "item_name": "Pembersihan Lokasi",
            "volume": 100.0,
            "unit": "m2",
            "unit_price": 15000.0,
            "total_price": 1500000.0,
            "ahsp_code": "A.2.2.1.1",
            "ahsp_status": "mapped_high"
          }
        ]
      }
    ]
  }
}
```

---

### 3.3 Ambil Spesifik Run ID
- **Method**: `GET`
- **Endpoint**: `/api/estimation-runs/{run_id_or_uuid}`

---

### 3.4 Update Baris Item Estimasi
Mengubah volume, harga satuan, atau mapping AHSP pada satu baris pekerjaan.

- **Method**: `PUT` atau `PATCH`
- **Endpoint**: `/api/estimation-items/{item_id_or_uuid}`
- **Request Body (JSON)**:
```json
{
  "volume": 150.0,
  "unit_price": 17500.0,
  "ahsp_code": "A.2.2.1.1",
  "ahsp_status": "mapped_high"
}
```
- **Response (`200 OK`)**: Mengembalikan data item yang telah diperbarui.

---

### 3.5 Hapus Item Estimasi
- **Method**: `DELETE`
- **Endpoint**: `/api/estimation-items/{item_id_or_uuid}`
- **Response (`200 OK`)**:
```json
{
  "status": 200,
  "success": true,
  "message": "Item estimasi berhasil dihapus."
}
```

---

## 4. Modul Katalog & Pemetaan AHSP

Endpoint untuk berinteraksi dengan database master AHSP (Analisis Harga Satuan Pekerjaan).

### 4.1 Katalog Master AHSP
- **Method**: `GET`
- **Endpoint**: `/api/ahsp/list`
- **Query Parameters**:
  - `page`: Nomor halaman (Default: `1`)
  - `limit`: Jumlah data per halaman (Default: `20`, Max: `100`)
  - `search`: Kata kunci pencarian nama atau kode AHSP

---

### 4.2 Pencarian Semantic AHSP
Mencari kandidat AHSP yang paling relevan berdasarkan kemiripan nama pekerjaan dan satuan.

- **Method**: `GET`
- **Endpoint**: `/api/ahsp/search`
- **Query Parameters**:
  - `q`: Query pencarian (contoh: `galian tanah keras`, `pasang bata merah`)
  - `limit`: Jumlah rekomendasi (Default: `10`)

---

### 4.3 Pemetaan Otomatis Satu Item ke AHSP (Map Item)
- **Method**: `POST`
- **Endpoint**: `/api/ahsp/map-item`
- **Request Body (JSON)**:
```json
{
  "item_name": "Plesteran dinding tebal 15mm 1sp:4pp",
  "item_unit": "m2"
}
```
- **Response (`200 OK`)**:
```json
{
  "item_name": "Plesteran dinding tebal 15mm 1sp:4pp",
  "ahsp_code": "A.4.4.2.1",
  "ahsp_name": "Pemasangan 1 m2 plesteran 1sp:4pp tebal 15 mm",
  "unit": "m2",
  "unit_price": 38500.0,
  "confidence_score": 0.94,
  "status": "mapped_high"
}
```

---

### 4.4 Statistik Database AHSP
- **Method**: `GET`
- **Endpoint**: `/api/ahsp/stats`
- **Response (`200 OK`)**: Menampilkan total item AHSP, kategori, dan versi dataset yang tersedia.

---

## ⚙️ Ringkasan Status Code HTTP

| Status Code | Makna | Kondisi Terjadi |
| :---: | :--- | :--- |
| `200 OK` | Sukses | Request GET/PUT/DELETE berhasil diproses |
| `201 Created` | Berhasil Dibuat | Data proyek baru berhasil dibuat |
| `400 Bad Request` | Request Salah | Payload JSON kosong atau format file tidak didukung |
| `404 Not Found` | Tidak Ditemukan | ID atau UUID proyek/item tidak ditemukan di database |
| `413 Payload Too Large` | Berkas Terlalu Besar | Ukuran berkas unggahan melebihi batas 500 MB |
| `422 Unprocessable` | Validasi Gagal | Data wajib (title/client) belum diisi |
| `500 Server Error` | Gagal Internal | Gangguan koneksi database atau microservice AI Python |
