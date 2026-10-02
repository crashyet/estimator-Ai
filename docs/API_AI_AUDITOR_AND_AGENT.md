# 🤖 Dokumentasi API Python: AI RAB Auditor & AI RAB Co-Pilot Agent

Dokumentasi komprehensif untuk modul kecerdasan buatan (**AI Services**) pada **Estimator API V2 (Python FastAPI)**:
1. **AI RAB Auditor (`/api/v2/ai/rab-audit`)** — Mesin inspeksi & validasi kelayakan RAB otomatis berbasis *Dual-Layer Architecture* (Rule-based & LLM Reasoning).
2. **AI RAB Co-Pilot Agent (`/api/v2/ai/rab-agent`)** — Asisten AI interaktif untuk manipulasi, substitusi material, penambahan item, dan optimasi biaya tabel RAB berbasis instruksi bahasa alami dengan kalkulasi matematika deterministik.

---

## 📌 Daftar Isi

1. [Ringkasan Arsitektur AI](#1-ringkasan-arsitektur-ai)
2. [Spesifikasi Teknis & Lingkungan](#2-spesifikasi-teknis--lingkungan)
3. [AI RAB Auditor (`POST /api/v2/ai/rab-audit`)](#3-ai-rab-auditor-post-apiv2airab-audit)
   - [3.1 Konsep Dual-Layer Validation](#31-konsep-dual-layer-validation)
   - [3.2 Formula Health Score & Status](#32-formula-health-score--status)
   - [3.3 Kontrak Data (Request & Response)](#33-kontrak-data-request--response)
   - [3.4 Contoh Payload Request](#34-contoh-payload-request)
   - [3.5 Contoh Payload Response](#35-contoh-payload-response)
   - [3.6 Database HSPK & Mekanisme Matching](#36-database-hspk--mekanisme-matching)
4. [AI RAB Co-Pilot Agent (`POST /api/v2/ai/rab-agent`)](#4-ai-rab-co-pilot-agent-post-apiv2airab-agent)
   - [4.1 Konsep Agentic Co-Pilot & Deterministic Math](#41-konsep-agentic-co-pilot--deterministic-math)
   - [4.2 Tipe Aksi Mutasi (Action Types)](#42-tipe-aksi-mutasi-action-types)
   - [4.3 Kontrak Data (Request & Response)](#43-kontrak-data-request--response-1)
   - [4.4 Contoh Payload Request](#44-contoh-payload-request-1)
   - [4.5 Contoh Payload Response](#45-contoh-payload-response-1)
   - [4.6 Grounding AHSP & Pencegahan Halusinasi](#46-grounding-ahsp--pencegahan-halusinasi)
5. [Integrasi Frontend & Backend CI4 Gateway](#5-integrasi-frontend--backend-ci4-gateway)
6. [Contoh Pemanggilan (cURL, Python, JavaScript)](#6-contoh-pemanggilan-curl-python-javascript)
7. [Error Handling & Resiliensi (Graceful Fallback)](#7-error-handling--resiliensi-graceful-fallback)
8. [Panduan Pengujian (Unit Test & CLI)](#8-panduan-pengujian-unit-test--cli)

---

## 1. Ringkasan Arsitektur AI

Layanan AI pada API V2 dirancang untuk melengkapi platform Estimator.id dengan kemampuan analitik dan operasional tingkat lanjut:

```mermaid
graph TD
    UI[Frontend Web / UI RAB] -->|HTTP POST| Gateway[Backend CI4 / Direct FastAPI]
    
    subgraph FastAPI V2 Engine [FastAPI Backend - Port 8200]
        Gateway --> RouterAudit[/api/v2/ai/rab-audit]
        Gateway --> RouterAgent[/api/v2/ai/rab-agent]
        
        subgraph AuditorEngine [RAB Auditor Module]
            RouterAudit --> Layer1[Layer 1: Deterministic Engine]
            RouterAudit --> Layer2[Layer 2: Gemini LLM Reasoning]
            Layer1 --> HSPK[(HSPK Local / MySQL Cache)]
            Layer1 --> RapidFuzz[RapidFuzz Normalizer]
            Layer2 --> GeminiAuditor[Gemini 2.5 Flash / Structured Output]
            Layer1 --> Scoring[Health Score Aggregator]
            Layer2 --> Scoring
        end
        
        subgraph AgentEngine [RAB Co-Pilot Agent Module]
            RouterAgent --> PreGrounding[AHSP Keyword Extractor]
            PreGrounding --> AHSPVector[(AHSP Vector Embeddings / Semantic DB)]
            RouterAgent --> PromptBuilder[Context & Prompt Builder]
            PromptBuilder --> GeminiAgent[Gemini 2.5 Flash Engine]
            GeminiAgent --> ActionParser[Action Normalizer & Validator]
            ActionParser --> DeterministicMath[Deterministic Math Calculator]
        end
    end
    
    Scoring -->|Health Score & Anomalies| UI
    DeterministicMath -->|Structured Actions & Cost Delta| UI
```

### Keunggulan Utama Desain:
* **Zero Math Hallucination**: Model LLM **tidak pernah** diminta menghitung perkalian harga akhir atau penjumlahan anggaran. Seluruh selisih biaya (`cost_delta`) dan dampak kumulatif (`cost_impact`) dihitung murni menggunakan matematika presisi tinggi di Python.
* **Dual-Layer Safety**: Validasi mendasar (volume nol, deviasi harga terhadap standar HSPK) ditangani algoritma deterministik secepat kilat tanpa biaya token AI.
* **Graceful Degradation**: Jika kuota AI habis atau koneksi internet terganggu, sistem tidak crash melainkan tetap memberikan hasil audit Layer 1.

---

## 2. Spesifikasi Teknis & Lingkungan

* **Framework**: FastAPI (Python 3.10+)
* **Worker & Threading**: AnyIO Worker Threadpool (`run_in_threadpool`) dengan batas konkurensi default 100 thread.
* **LLM Provider**: Google Gemini API via official SDK `google-genai` (Model rekomendasi: `gemini-2.5-flash`).
* **Fuzzy Matching**: `RapidFuzz` (token set ratio + normalisasi terminologi konstruksi).
* **Port Layanan**: `8200` (Default API V2).
* **Base Path**: `http://localhost:8200/api/v2/ai`

### Variabel Environment Relevan (`api_v2/.env`)

```ini
# API Key Google Gemini (Wajib untuk Layer 2 Audit & Co-Pilot Agent)
GEMINI_API_KEY=AIzaSy...

# Model Gemini yang digunakan (Default: gemini-2.5-flash)
GEMINI_MODEL=gemini-2.5-flash

# Konfigurasi Server
HOST=0.0.0.0
PORT=8200
WORKERS=1
MAX_CONCURRENT_THREADS=100
```

---

## 3. AI RAB Auditor (`POST /api/v2/ai/rab-audit`)

Endpoint ini melakukan pemindaian otomatis terhadap seluruh item pekerjaan pada dokumen RAB untuk mendeteksi kesalahan volume, indikasi markup/dumping harga satuan, dan kelalaian item pendukung (*missing scope*).

* **URL**: `/api/v2/ai/rab-audit`
* **Method**: `POST`
* **Headers**: `Content-Type: application/json`
* **File Implementasi**:
  - Router: [`api_v2/routers/rab_audit.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/routers/rab_audit.py)
  - Engine Layer 1: [`api_v2/rab_auditor/engine.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/engine.py)
  - Engine Layer 2: [`api_v2/rab_auditor/llm_engine.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/llm_engine.py)
  - Scoring & Agregasi: [`api_v2/rab_auditor/scoring.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/scoring.py)
  - Skema Pydantic: [`api_v2/rab_auditor/schemas.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/schemas.py)

---

### 3.1 Konsep Dual-Layer Validation

Pemeriksaan dibagi menjadi dua lapisan independen:

| Layer | Komponen | Cakupan Validasi | Karakteristik |
|---|---|---|---|
| **Layer 1** | **Deterministic Engine** | 1. **Volume Kosong / Nol**: Memeriksa item dengan `volume <= 0.0` atau `null`.<br>2. **Deviasi Harga vs HSPK**: Membandingkan `unit_price` user dengan standar HSPK per kabupaten/kota. | Kecepatan < 200ms, tanpa biaya token LLM, 100% konsisten. |
| **Layer 2** | **LLM Contextual Reasoning** | 1. **Missing Scope Detection**: Mendeteksi kelalaian item pekerjaan penting berdasar tipe bangunan (e.g., cor beton tanpa besi/bekisting).<br>2. **Volume Sanity Check**: Memeriksa outlier rasio volume terhadap luas bangunan (`building_area_m2`). | Menggunakan model multimodal reasoning dengan Structured Output JSON Schema. |

#### Ambang Batas Deviasi Harga (Layer 1):

$$\Delta\% = \frac{\text{Harga User} - \text{Harga Acuan HSPK}}{\text{Harga Acuan HSPK}} \times 100\%$$

| Rentang Deviasi ($\Delta\%$) | Klasifikasi | Severity | Keterangan |
|---|---|---|---|
| $-15.0\% \le \Delta\% \le +15.0\%$ | **NORMAL** | - | Harga wajar sesuai standar pasar daerah. |
| $+15.0\% < \Delta\% \le +35.0\%$ | **PRICE_OVERPRICED** | `WARNING` | Harga agak tinggi, perlu dicek kembali. |
| $\Delta\% > +35.0\%$ | **PRICE_OVERPRICED** | `CRITICAL` | Indikasi markup berat / salah input nominal. |
| $\Delta\% < -25.0\%$ | **PRICE_UNDERPRICED** | `WARNING` | Terlalu murah; risiko mutu rendah / proyek mangkrak. |

---

### 3.2 Formula Health Score & Status

Health Score dihitung secara otomatis dengan sistem pengurangan penalti dari baseline sempurna (100 poin):

$$\text{Health Score} = \max\Big(0,\; 100 - (15 \times N_{\text{CRITICAL}}) - (5 \times N_{\text{WARNING}}) - (10 \times N_{\text{MISSING\_SCOPE}})\Big)$$

#### Matriks Status Kesehatan:

| Skor | Status | Warna Badge UI | Makna Operasional |
|:---:|:---:|:---:|---|
| **90 – 100** | `EXCELLENT` | 🟢 Hijau | RAB prima, deviasi minimal, siap disetujui/cetak. |
| **75 – 89** | `GOOD` | 🟢 Hijau Muda | Layak jalan, terdapat beberapa catatan minor. |
| **50 – 74** | `NEEDS_REVIEW` | 🟡 Kuning | Perlu revisi dan tinjauan ulang oleh estimator/QS. |
| **0 – 49** | `POOR` | 🔴 Merah | Kritis! Terdapat volume nol atau deviasi markup berbahaya. |

---

### 3.3 Kontrak Data (Request & Response)

#### Skema Request (`RABAuditRequest`)
- `project_id` (*string*, required): UUID proyek.
- `project_context` (*object*, required):
  - `project_name` (*string*): Nama proyek.
  - `building_type` (*string*): Contoh `"Rumah Tinggal"`, `"Gedung Kantor"`, `"Ruko"`.
  - `location` (*object*, required):
    - `province` (*string*): Contoh `"Jawa Tengah"`.
    - `city_regency` (*string*): Contoh `"Banyumas"`.
  - `building_area_m2` (*float*): Luas total bangunan dalam m².
  - `number_of_floors` (*int*): Jumlah lantai bangunan.
  - `currency` (*string*, optional, default: `"IDR"`).
- `items` (*array of objects*, required):
  - `id` (*int*, required): ID unik item pada tabel.
  - `category` (*string*): Kategori pekerjaan (e.g. `"Pekerjaan Struktur"`).
  - `description` (*string*, required): Uraian spesifik pekerjaan.
  - `ahsp_code` (*string*, optional): Kode AHSP jika telah dipetakan.
  - `volume` (*float*, required): Kuantitas volume pekerjaan.
  - `unit` (*string*, required): Satuan (e.g. `"m3"`, `"m2"`, `"kg"`, `"titik"`).
  - `unit_price` (*float*, required): Harga satuan pekerjaan (Rp).
  - `total_price` (*float*, required): Total harga (`volume * unit_price`).

---

### 3.4 Contoh Payload Request

```json
{
  "project_id": "proj-98234-a1",
  "project_context": {
    "project_name": "Pembangunan Rumah Tinggal 2 Lantai",
    "building_type": "Rumah Tinggal",
    "location": {
      "province": "Jawa Tengah",
      "city_regency": "Banyumas"
    },
    "building_area_m2": 150.0,
    "number_of_floors": 2,
    "currency": "IDR"
  },
  "items": [
    {
      "id": 1,
      "category": "Pekerjaan Pondasi",
      "description": "Galian tanah pondasi batu kali",
      "ahsp_code": "",
      "volume": 0.0,
      "unit": "m3",
      "unit_price": 85000.0,
      "total_price": 0.0
    },
    {
      "id": 2,
      "category": "Pekerjaan Struktur",
      "description": "Pengecoran beton mutu f'c 19,3 MPa (K 225)",
      "ahsp_code": "",
      "volume": 25.0,
      "unit": "m3",
      "unit_price": 2400000.0,
      "total_price": 60000000.0
    },
    {
      "id": 3,
      "category": "Pekerjaan Pasangan",
      "description": "Pasang dinding bata merah tebal 1/2 batu camp 1sp:4pp",
      "ahsp_code": "",
      "volume": 120.0,
      "unit": "m2",
      "unit_price": 145000.0,
      "total_price": 17400000.0
    }
  ]
}
```

---

### 3.5 Contoh Payload Response

```json
{
  "status": "success",
  "data": {
    "audit_timestamp": "2026-09-22T02:15:30Z",
    "health_score": 60,
    "health_status": "NEEDS_REVIEW",
    "summary": {
      "total_items_checked": 3,
      "critical_count": 2,
      "warning_count": 0,
      "missing_scope_count": 1
    },
    "anomalies": [
      {
        "item_id": 1,
        "type": "VOLUME_ZERO",
        "severity": "CRITICAL",
        "field": "volume",
        "message": "Volume pekerjaan bernilai 0.00 m3 pada item \"Galian tanah pondasi batu kali\".",
        "recommendation": "Isi estimasi volume sesuai dimensi aktual proyek. Pastikan rumus luas/volume telah dihitung dengan benar.",
        "current_value": 0.0,
        "benchmark_value": null,
        "deviation_percent": null
      },
      {
        "item_id": 2,
        "type": "PRICE_OVERPRICED",
        "severity": "CRITICAL",
        "field": "unit_price",
        "message": "Harga satuan lebih tinggi +104.2% dari standar HSPK (T.01.a: Membuat 1 m3 beton mutu f'c = 19,3 MPa (K 225)).",
        "recommendation": "Harga acuan HSPK: Rp 1,175,000 / m3. Pertimbangkan harga di rentang wajar (±15% dari acuan).",
        "current_value": 2400000.0,
        "benchmark_value": 1175000.0,
        "deviation_percent": 104.2
      }
    ],
    "missing_scopes": [
      {
        "category": "Pekerjaan Struktur",
        "missing_item": "Pembesian / Tulangan Besi Beton & Bekisting",
        "confidence": 0.95,
        "reason": "Terdapat pekerjaan pengecoran beton struktur (K 225) namun tidak ditemukan item pembesian dan cetakan/bekisting."
      }
    ]
  }
}
```

---

### 3.6 Database HSPK & Mekanisme Matching

Proses pencocokan item pengguna ke database standar acuan harga (`HSPKItem`) dilakukan melalui pipeline multi-tahap:
1. **Pembersihan & Normalisasi Teks** ([`normalize_description`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/engine.py#L43-L63)):
   - Menghilangkan sitasi peraturan birokrasi (contoh: `(lihat peraturan menteri...)`).
   - Standardisasi simbol pecahan (`½` $\to$ `1/2`, `¼` $\to$ `1/4`).
   - Penyelarasan terminologi tukang (`1/2 batu` $\to$ `1/2 bata`, `1 batu` $\to$ `1 bata`).
   - Normalisasi rasio campuran spesi (`1 sp : 4 pp` $\to$ `1sp:4pp`).
2. **Exact Lookup**: Pencarian instan bila baris memiliki kode referensi AHSP (`ahsp_code`).
3. **Fuzzy Scoring**: `RapidFuzz` `token_set_ratio` dengan bobot tambahan $+5$ poin jika satuan item sama.
4. **Fallback Matching**: Keyword counter berbasis kata benda material jika library fuzzy tidak tersedia.

---

## 4. AI RAB Co-Pilot Agent (`POST /api/v2/ai/rab-agent`)

Endpoint ini bertindak sebagai asisten pintar (Co-Pilot) interaktif. Pengguna cukup memberikan instruksi dalam bahasa manusia sehari-hari, dan Agent akan membaca konteks seluruh tabel RAB aktif, menalar perubahan yang dibutuhkan, lalu mengembalikan daftar instruksi mutasi terstruktur (*action plan*) beserta pratinjau kalkulasi biayanya.

* **URL**: `/api/v2/ai/rab-agent`
* **Method**: `POST`
* **Headers**: `Content-Type: application/json`
* **File Implementasi**:
  - Router: [`api_v2/routers/rab_agent.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/routers/rab_agent.py)
  - Core Reasoning Engine: [`api_v2/rab_agent/engine.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_agent/engine.py)
  - Deterministic Math & Tools: [`api_v2/rab_agent/tools.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_agent/tools.py)
  - System Prompts: [`api_v2/rab_agent/prompts.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_agent/prompts.py)
  - Skema Pydantic: [`api_v2/rab_agent/schemas.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_agent/schemas.py)

---

### 4.1 Konsep Agentic Co-Pilot & Deterministic Math

AI Agent tidak langsung mengubah database atau tabel tanpa persetujuan pengguna. Alur kerjanya menganut paradigma **Human-in-the-Loop**:

```
[User Prompt] 
      ↓
[LLM Reasoning + AHSP Grounding]
      ↓
[Generate Proposal: Structured Actions]
      ↓
[Deterministic Python Math Engine]
      ↓
[Preview Diff & Cost Impact di Frontend Drawer]
      ↓
[User Klik: 'Terapkan Perubahan' / 'Batalkan']
```

#### Rumus Matematika Deterministik Per Aksi:
- **`ADD_ITEM`**: $\text{cost\_delta} = +\left(\text{new\_volume} \times \text{new\_unit\_price}\right)$
- **`DELETE_ITEM`**: $\text{cost\_delta} = -\left(\text{old\_volume} \times \text{old\_unit\_price}\right)$
- **`UPDATE_ITEM`**: $\text{cost\_delta} = \left(\text{new\_volume} \times \text{new\_unit\_price}\right) - \left(\text{old\_volume} \times \text{old\_unit\_price}\right)$
- **`cost_impact`**: $\sum \text{cost\_delta}$ (Nilai positif = anggaran bertambah; Nilai negatif = penghematan biaya).

---

### 4.2 Tipe Aksi Mutasi (Action Types)

| `action_type` | Fungsi | Field Kunci | Contoh Kasus Penggunaan |
|---|---|---|---|
| `UPDATE_ITEM` | Mengubah spesifikasi, harga satuan, atau volume item eksisting. | `target_item_id`, `changes`, `old_values` | *"Ganti semua lantai keramik 40x40 jadi granit 60x60"* |
| `ADD_ITEM` | Menambahkan baris pekerjaan baru ke dalam WBS/kategori tertentu. | `target_section_id`, `target_category`, `changes` | *"Tambahkan 12 titik stop kontak Panasonic di instalasi listrik"* |
| `DELETE_ITEM` | Menghapus item pekerjaan tertentu dari tabel RAB. | `target_item_id`, `old_values` | *"Hapus pekerjaan bongkaran dinding lama karena tidak diperlukan"* |

---

### 4.3 Kontrak Data (Request & Response)

#### Skema Request (`RABAgentRequest`)
- `project_id` (*string*, required): UUID proyek.
- `prompt` (*string*, required): Perintah bahasa alami dari pengguna.
- `items` (*array of objects*, required): Snapshot seluruh item tabel saat ini (`RABItemContext`):
  - `id` (*int*): ID item.
  - `section_id` (*int*, optional): ID seksi/WBS kategori.
  - `category` (*string*): Nama kategori.
  - `description` (*string*): Uraian pekerjaan.
  - `ahsp_code` (*string*, optional): Kode AHSP.
  - `volume` (*float*): Kuantitas saat ini.
  - `unit` (*string*): Satuan.
  - `unit_price` (*float*): Harga satuan saat ini.
  - `total_price` (*float*): Total harga saat ini.
- `project_context` (*object*, optional): Konteks proyek (nama, tipe, lokasi, luas bangunan).
- `history` (*array of objects*, optional): Riwayat percakapan sebelumnya (`role`: `"user"` | `"assistant"`, `content`: *string*).

---

### 4.4 Contoh Payload Request

Pengguna ingin melakukan *value engineering* (substitusi material):

```json
{
  "project_id": "proj-98234-a1",
  "prompt": "Tolong ganti lantai keramik 40x40 dengan granit tile 60x60, dan hapus pekerjaan pembersihan akhir.",
  "project_context": {
    "project_name": "Renovasi Rumah Mewah",
    "building_type": "Rumah Tinggal",
    "location": {
      "province": "Jawa Tengah",
      "city_regency": "Banyumas"
    },
    "building_area_m2": 180.0
  },
  "items": [
    {
      "id": 101,
      "section_id": 3,
      "category": "Pekerjaan Lantai & Dinding",
      "description": "Pasang lantai keramik 40x40 cm motif polos",
      "ahsp_code": "A.4.4.3.35",
      "volume": 85.0,
      "unit": "m2",
      "unit_price": 125000.0,
      "total_price": 10625000.0
    },
    {
      "id": 102,
      "section_id": 6,
      "category": "Pekerjaan Pembersihan",
      "description": "Pembersihan akhir lapangan dan sisa puing",
      "ahsp_code": "",
      "volume": 1.0,
      "unit": "ls",
      "unit_price": 1500000.0,
      "total_price": 1500000.0
    }
  ],
  "history": []
}
```

---

### 4.5 Contoh Payload Response

```json
{
  "reply_message": "Saya telah menyusun 2 usulan perubahan: menaikkan spesifikasi penutup lantai ke granit tile 60x60 serta menghapus pekerjaan pembersihan akhir. Total estimasi biaya bertambah sebesar Rp 11,250,000.",
  "actions": [
    {
      "action_type": "UPDATE_ITEM",
      "target_item_id": 101,
      "target_section_id": 3,
      "target_category": "Pekerjaan Lantai & Dinding",
      "description": "Ganti penutup lantai menjadi Granit Tile 60x60 cm",
      "changes": {
        "item_name": "Pasang lantai homogeneous tile / granit 60x60 cm",
        "volume": 85.0,
        "unit": "m2",
        "unit_price": 275000.0,
        "ahsp_code": "A.4.4.3.36"
      },
      "old_values": {
        "item_name": "Pasang lantai keramik 40x40 cm motif polos",
        "volume": 85.0,
        "unit": "m2",
        "unit_price": 125000.0,
        "total_price": 10625000.0
      },
      "cost_delta": 12750000.0
    },
    {
      "action_type": "DELETE_ITEM",
      "target_item_id": 102,
      "target_section_id": 6,
      "target_category": "Pekerjaan Pembersihan",
      "description": "Hapus pekerjaan pembersihan akhir",
      "changes": {},
      "old_values": {
        "item_name": "Pembersihan akhir lapangan dan sisa puing",
        "volume": 1.0,
        "unit": "ls",
        "unit_price": 1500000.0,
        "total_price": 1500000.0
      },
      "cost_delta": -1500000.0
    }
  ],
  "cost_impact": 11250000.0,
  "affected_items_count": 2,
  "timestamp": "2026-09-22T02:20:15.120531Z"
}
```

---

### 4.6 Grounding AHSP & Pencegahan Halusinasi

Agar agen tidak mengarang nama atau harga pekerjaan baru secara fiktif:
1. **Pre-Grounding Keyword Extraction**: Sebelum prompt dikirim ke Gemini, regex extractor mengekstrak kata kunci target material baru dari prompt pengguna (misal: `"granit"`, `"stop kontak"`).
2. **Candidate Search**: Fungsi [`search_ahsp_candidates`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_agent/tools.py#L30-L54) mencari item pekerjaan resmi di database AHSP.
3. **Prompt Injection Hint**: Hasil kandidat resmi disuntikkan ke dalam prompt sebagai panduan (*grounding data*).
4. **Deterministic Deltas**: Fungsi [`calculate_action_deltas`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_agent/tools.py#L56-L115) mengeksekusi perhitungan delta harga menggunakan data snapshot lama pengguna sehingga nominal lama tidak bisa dipalsukan oleh LLM.

---

## 5. Integrasi Frontend & Backend CI4 Gateway

Pada aplikasi Estimator.id, antarmuka web memanggil API ini baik secara langsung ataupun melalui Backend CodeIgniter 4:

### URL Endpoint Pemanggilan:
* Direct Python V2:
  - Audit: `http://127.0.0.1:8200/api/v2/ai/rab-audit`
  - Agent: `http://127.0.0.1:8200/api/v2/ai/rab-agent`
* View Pemanggil Eksisting:
  - File: [`backend/app/Views/projects/rab.php`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/backend/app/Views/projects/rab.php#L1558-L1562)

### Alur Kerja UI Frontend:
1. **Pemicu Audit**:
   - Pengguna mengklik tombol **"Audit Kelayakan RAB"** di menu atas atau FAB.
   - Frontend mengumpulkan baris tabel aktif menjadi format array `items`.
   - Menampilkan loader skeleton pada panel audit.
   - Hasil audit diterima $\to$ Health Score di-render dengan gauge meter / badge warna, anomali ditampilkan di drawer list, dan baris tabel yang bermasalah diberi highlight warna (merah untuk CRITICAL, kuning untuk WARNING).
2. **Pemicu AI Co-Pilot**:
   - Pengguna membuka panel chat **"AI RAB Co-Pilot"** di sisi kanan layar.
   - Pengguna mengetik perintah, misal: *"Ganti cat dinding interior ke Nippon Paint Vinilex"*.
   - Agen membalas dengan penjelasan ramah dan tombol konfirmasi **"Terapkan 1 Perubahan (+Rp 450,000)"**.
   - Saat disetujui, frontend menerapkan mutasi baris ke tabel HTML dan memicu fungsi auto-save ke database CI4.

---

## 6. Contoh Pemanggilan (cURL, Python, JavaScript)

### 6.1 Menggunakan cURL

#### Menjalankan Audit:
```bash
curl -X POST "http://localhost:8200/api/v2/ai/rab-audit" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "demo-proj-01",
    "project_context": {
      "project_name": "Renovasi Rumah",
      "building_type": "Rumah Tinggal",
      "location": {
        "province": "Jawa Tengah",
        "city_regency": "Banyumas"
      },
      "building_area_m2": 100.0,
      "number_of_floors": 1
    },
    "items": [
      {
        "id": 1,
        "category": "Pekerjaan Tanah",
        "description": "Galian tanah biasa",
        "volume": 0.0,
        "unit": "m3",
        "unit_price": 75000.0,
        "total_price": 0.0
      }
    ]
  }'
```

#### Menjalankan Agent:
```bash
curl -X POST "http://localhost:8200/api/v2/ai/rab-agent" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": "demo-proj-01",
    "prompt": "Ubah harga galian tanah jadi 90000 dan set volume jadi 15 m3",
    "items": [
      {
        "id": 1,
        "category": "Pekerjaan Tanah",
        "description": "Galian tanah biasa",
        "volume": 0.0,
        "unit": "m3",
        "unit_price": 75000.0,
        "total_price": 0.0
      }
    ]
  }'
```

---

### 6.2 Menggunakan Python (`requests` / `httpx`)

```python
import requests

API_URL = "http://localhost:8200/api/v2/ai/rab-audit"

payload = {
    "project_id": "proyek-test-01",
    "project_context": {
        "project_name": "Ruko 3 Lantai",
        "building_type": "Ruko",
        "location": {"province": "Jawa Tengah", "city_regency": "Banyumas"},
        "building_area_m2": 240.0,
        "number_of_floors": 3
    },
    "items": [
        {
            "id": 10,
            "category": "Pekerjaan Struktur",
            "description": "Cor Beton K 250",
            "volume": 45.0,
            "unit": "m3",
            "unit_price": 1350000.0,
            "total_price": 60750000.0
        }
    ]
}

response = requests.post(API_URL, json=payload, timeout=30)
if response.status_code == 200:
    audit_data = response.json()["data"]
    print(f"Health Score: {audit_data['health_score']}/100 ({audit_data['health_status']})")
    print(f"Temuan Anomali: {len(audit_data['anomalies'])}")
    print(f"Missing Scopes: {len(audit_data['missing_scopes'])}")
else:
    print(f"Error {response.status_code}: {response.text}")
```

---

### 6.3 Menggunakan JavaScript (`fetch` Async/Await)

```javascript
async function requestRabAudit(projectId, context, rabRows) {
  try {
    const response = await fetch('http://127.0.0.1:8200/api/v2/ai/rab-audit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        project_id: projectId,
        project_context: context,
        items: rabRows
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP Error ${response.status}`);
    }

    const resJson = await response.json();
    console.log('Health Score:', resJson.data.health_score);
    return resJson.data;
  } catch (error) {
    console.error('Gagal menjalankan AI RAB Audit:', error);
    throw error;
  }
}
```

---

## 7. Error Handling & Resiliensi (Graceful Fallback)

| Kondisi / Skenario Error | Respon Sistem | Status Code |
|---|---|:---:|
| `items` kosong (`[]`) | Return error validasi data masukan. | `400 Bad Request` |
| `prompt` kosong pada Co-Pilot | Return pesan peringatan instruksi kosong. | `400 Bad Request` |
| Database HSPK daerah tidak ditemukan | Layer 1 tetap berjalan untuk deteksi **Volume Zero**, melewatkan komparasi harga tanpa error. | `200 OK` |
| Kuota Gemini habis / API timeout / koneksi putus | **Graceful Fallback**: Layer 2 dilewati (`missing_scopes = []`), hasil audit Layer 1 tetap dikembalikan ke user beserta log warning. | `200 OK` |
| `GEMINI_API_KEY` tidak terpasang di `.env` | Co-Pilot Agent mengembalikan `reply_message` ramah bahwa AI belum dikonfigurasi tanpa membuat aplikasi web crash. | `200 OK` |

---

## 8. Panduan Pengujian (Unit Test & CLI)

Proyek ini telah dilengkapi dengan rangkaian unit test komprehensif menggunakan `pytest`.

### Menjalankan Unit Tests:

```bash
cd api_v2

# Aktifkan virtualenv
source .venv/bin/activate

# 1. Jalankan seluruh test suite AI Agent & Auditor
PYTHONPATH=. pytest tests/test_rab_agent.py rab_auditor/tests/test_engine.py -v

# 2. Menjalankan test kalkulasi deterministik saja
PYTHONPATH=. pytest tests/test_rab_agent.py -k "test_action_cost_deltas_calculation" -v

# 3. Menjalankan test normalisasi & scoring auditor
PYTHONPATH=. pytest rab_auditor/tests/test_engine.py -k "test_scoring" -v
```

### File Sampel Uji Coba:
- Sample Request Auditor: [`api_v2/rab_auditor/tests/sample_request.json`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/tests/sample_request.json)
- Test Script Auditor: [`api_v2/rab_auditor/tests/test_engine.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/rab_auditor/tests/test_engine.py)
- Test Script Agent: [`api_v2/tests/test_rab_agent.py`](file:///home/adhit/Desktop/Ngulik/magang_beecons/estimator/api_v2/tests/test_rab_agent.py)
