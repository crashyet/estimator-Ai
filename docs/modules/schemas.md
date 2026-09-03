# `src/schemas.py` — Pydantic Data Schemas

File ini mendefinisikan seluruh model data (request/response) yang digunakan di seluruh backend `api_v2`.

---

## Daftar Model (Classes)

### `AHSPCandidateItem`
Satu kandidat item AHSP dari hasil vector semantic search.

Digunakan pada field `ahsp_candidates` di `EstimateItem` untuk menampilkan pilihan rekomendasi AHSP dengan skor kemiripan. Pengguna dapat memilih atau mengganti mapping secara interaktif di frontend melalui popover.

| Field | Tipe | Default | Keterangan |
|---|---|---|---|
| `id_pekerjaan` | `str` | `""` | ID unik item AHSP (contoh: `"2.2.1.6.6"`) |
| `nama_pekerjaan` | `str` | `""` | Nama pekerjaan standar AHSP |
| `satuan` | `str` | `""` | Satuan standar AHSP (`m2`, `m3`, `kg`, dll.) |
| `score` | `float` | `0.0` | Similarity score dari vector search (0.0 – 1.0) |

---

### `CoverageAuditItem`
Item audit kelengkapan untuk memastikan setiap lembar gambar PDF menghasilkan setidaknya satu item WBS.

Dikirimkan pada field `coverage_audit` di `DynamicTakeoffResponse` ketika AI melakukan analisis PDF multi-halaman. Berguna untuk debug jika ada lembar gambar yang terlewat.

| Field | Tipe | Default | Keterangan |
|---|---|---|---|
| `sheet` | `str` | (wajib) | Kode/judul lembar gambar (contoh: `"ARS-01"`, `"STR-03"`) |
| `produced_items` | `List[str]` | `[]` | Daftar ID item yang dihasilkan dari lembar ini |
| `status` | `str` | `"ok"` | Status audit: `"ok"` atau `"skipped"` |

---

### `BIMElementQuantity`
Kuantitas elemen 3D BIM per kombinasi unik (level, kategori IFC, family, material).

Dihasilkan oleh `BIMEntityExtractor` (`bim_parser.py`) atau `APSConverter` (`aps_client.py`). Hasil agregasi diformat sebagai JSON payload lalu dikirim ke `CADLLMEstimator.analyze_bim_payload()`.

| Field | Tipe | Default | Keterangan |
|---|---|---|---|
| `level` | `str` | `"Unassigned Level"` | Nama lantai/level bangunan |
| `category` | `str` | (wajib) | Kategori IFC (`IfcWall`, `IfcColumn`, `IfcBeam`, dll.) |
| `family_name` | `str` | (wajib) | Nama tipe/family elemen BIM |
| `material` | `str` | `"Standard Material"` | Material elemen |
| `count` | `int` | `1` | Jumlah elemen dalam grup |
| `total_volume_m3` | `float` | `0.0` | Total volume bersih (m³) |
| `total_area_m2` | `float` | `0.0` | Total luas bersih (m²) |
| `total_length_m` | `float` | `0.0` | Total panjang (m) |

---

### `ProjectInfo`
Metadata identitas proyek konstruksi.

| Field | Tipe | Default | Keterangan |
|---|---|---|---|
| `title` | `str` | `"Analisis CAD DWG"` | Nama/judul proyek |
| `client` | `str` | `"Client"` | Nama klien atau pemilik proyek |
| `status` | `str` | `"Perencanaan"` | Status proyek |

---

### `EstimateItem`
Satu item pekerjaan WBS yang dihasilkan AI dari analisis CAD/BIM/PDF/Image.

> **Penting**: `volume` HARUS bernilai positif (`> 0.0`). Field `ahsp_*` diisi oleh `AHSPMapperEngine` setelah AI selesai.

| Field | Tipe | Default | Keterangan |
|---|---|---|---|
| `id` | `str` | (wajib) | ID unik item (`"item-A-1"`) |
| `type` | `str` | `"item"` | Selalu `"item"` |
| `sectionCode` | `str` | (wajib) | Kode seksi induk (`"A"`, `"B"`, ...) |
| `no` | `int` | (wajib) | Nomor urut dalam seksi |
| `code` | `str` | `"CUSTOM"` | Kode AHSP atau kode kustom |
| `name` | `str` | (wajib) | Uraian pekerjaan dari AI |
| `volume` | `float` | (wajib) | Volume hasil kalkulasi AI (**HARUS > 0.0**) |
| `unit` | `str` | (wajib) | Satuan (`m3`, `m2`, `m1`, `unit`, `kg`, `ls`, dll.) |
| `confidence` | `str` | `"high"` | Keyakinan AI: `"high"` atau `"medium"` |
| `warning_note` | `str?` | `None` | Rumus kalkulasi volume |
| `ahsp_code` | `str?` | `None` | Kode AHSP yang di-mapping |
| `ahsp_name` | `str?` | `None` | Nama AHSP standar |
| `ahsp_unit` | `str?` | `None` | Satuan AHSP standar |
| `ahsp_score` | `float?` | `None` | Similarity score mapping (0.0–1.0) |
| `ahsp_status` | `str` | `"unmapped"` | Status: `"mapped_high"`, `"mapped_medium"`, atau `"unmapped"` |
| `ahsp_candidates` | `List[AHSPCandidateItem]?` | `None` | Top-3 kandidat AHSP untuk popover |

**`ahsp_status` → warna badge frontend**:
- `mapped_high` → 🟢 hijau
- `mapped_medium` → 🟡 kuning
- `unmapped` → 🔴 abu/merah

---

### `EstimateSection`
Header seksi WBS yang mengelompokkan item-item pekerjaan.

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | `str` | ID unik (`"sec-A"`) |
| `type` | `str` | Selalu `"section"` |
| `code` | `str` | Kode seksi (`A`, `B`, `C`, ...) |
| `name` | `str` | Nama kategori WBS dari AI |

---

### `WBSSectionBlock`
Menggabungkan satu `EstimateSection` dengan daftar `EstimateItem` di bawahnya.

---

### `DynamicTakeoffResponse`
Root response dari seluruh endpoint `/api/v2/takeoff/*`.

| Field | Tipe | Keterangan |
|---|---|---|
| `project` | `ProjectInfo` | Metadata proyek |
| `project_summary` | `str` | Ringkasan analisis AI |
| `wbs_sections` | `List[WBSSectionBlock]` | Daftar seksi + item WBS |
| `coverage_audit` | `List[CoverageAuditItem]?` | Audit kelengkapan lembar PDF |

**Method `to_frontend_format()`**: Meratakan struktur hierarkis menjadi flat array `items` yang langsung dirender oleh `Anggaran.jsx`.

```python
{
    "project": {...},
    "items": [section_row, item_row, item_row, section_row, ...],
    "coverage_audit": [...] | None,
    "raw_llm_response": {...}
}
```
