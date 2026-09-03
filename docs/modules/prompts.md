# `src/prompts.py` — AI Prompt Repository

**Single source of truth** untuk seluruh instruksi sistem AI dan template perintah pengguna yang digunakan oleh `CADLLMEstimator`.

> **Panduan Kontribusi**: Untuk menambah atau mengubah aturan QS (misal: aturan pembesian, AHSP action verb baru), cukup edit konstanta prompt di file ini. Tidak perlu menyentuh kode logika eksekusi di `llm_estimator.py`.

---

## System Prompt Constants

### `CAD_SYSTEM_PROMPT`
Instruksi AI untuk analisis file vektor CAD (`.dwg`, `.dxf`, `.svg`, `.plt`).

**Aturan kritis yang dikandung**:
- **Unit normalisasi**: CAD Indonesia menggunakan mm/cm → wajib dibagi 1000 (mm) atau 100 (cm) untuk konversi ke meter.
- **Dimensi eksak**: Hanya gunakan angka yang tertera pada drawing. Dilarang menebak atau menggunakan default statis.
- **Volume positif**: Setiap item wajib volume > 0.0.
- **AHSP naming convention**: Prefix action verb standar (`Pemasangan`, `Pengecoran`, `Plesteran`, dll.).

---

### `PDF_SYSTEM_PROMPT`
Instruksi AI untuk analisis set gambar DED PDF multi-halaman.

**Aturan tambahan khusus PDF**:
1. **Inventarisasi lembar**: Baca daftar semua sheet (ARS-xx, STR-xx) sebelum ekstraksi item.
2. **Coverage completeness**: Setiap lembar gambar WAJIB menghasilkan minimal 1 item — tidak boleh ada lembar yang terlewat tanpa penjelasan.
3. **`coverage_audit` field**: Harus diisi sebagai audit pembuktian bahwa tidak ada lembar gambar yang di-skip.
4. **Elemen repeatable**: Hitung tiap occurrence pintu/jendela/kolom secara eksplisit per lantai.

---

### `IMAGE_SYSTEM_PROMPT`
Instruksi AI untuk analisis gambar cetak biru arsitektur/struktur (`.jpg`, `.png`, `.webp`).

Sama dengan `CAD_SYSTEM_PROMPT` tetapi disesuaikan untuk input gambar (bukan teks entitas vektor).

---

### `BIM_SYSTEM_PROMPT`
Instruksi AI untuk pemetaan kuantitas parametrik 3D BIM (IFC/Revit).

Sistem **Two-Tier Quantity**:

| Tier | Sumber | Confidence | Keterangan |
|---|---|---|---|
| **TIER 1** | Langsung dari BIM payload | `"high"` | Volume eksak dari IFC (`total_volume_m3`, `total_area_m2`, dll.) |
| **TIER 2** | Derived/Implied | `"medium"` atau `"low"` | Item yang tidak ada di BIM tapi wajib ada di RAB |

**Item TIER 2 yang wajib dihasilkan**:
- Pekerjaan Persiapan: Pembersihan Lapangan, Bouwplank
- Pekerjaan Tanah: Galian, Urugan Pasir, Urugan Kembali, Pemadatan
- Bekisting: Kolom, Balok, Sloof, Plat Lantai
- Pembesian: estimasi rasio kg/m³ standar (80–120 kg/m³ kolom, 100–150 kg/m³ balok)
- Finishing: Plesteran (2 sisi), Acian, Pengecatan Dinding & Plafon, Keramik Lantai

---

## Prompt Builder Functions

### `build_cad_user_prompt(text_payload, project_name, client_name)`
Membangun user turn prompt untuk analisis CAD.

- **Input**: Teks entitas CAD dari `CADEntityExtractor.format_to_llm_payload()`
- **Output**: String perintah lengkap untuk Gemini/Primary API

### `build_pdf_user_prompt(filename, project_name, client_name)`
Membangun user turn prompt untuk analisis PDF multi-halaman.

Berisi 6 langkah QS terstruktur:
1. Inventarisasi semua lembar gambar
2. Tentukan skala & unit
3. Sanity check luas tapak
4. Ekstraksi item per lembar
5. Susun WBS sendiri
6. Audit kelengkapan (`coverage_audit`)

### `build_image_user_prompt(filename, project_name, client_name)`
Membangun user turn prompt untuk analisis gambar cetak biru.

### `build_bim_user_prompt(bim_payload, project_name, client_name)`
Membangun user turn prompt untuk analisis BIM 3D.

- **Input**: JSON string dari `BIMEntityExtractor.format_to_llm_payload()`
- **Target**: Minimal 30 work items RAB komprehensif
