# `src/llm_estimator.py` — Gemini LLM Takeoff Execution Engine

Mengorkestrasi panggilan ke Generative AI (Google Gemini atau Primary OpenAI-compatible API) untuk menghasilkan output WBS dan RAB dari berbagai format file konstruksi.

---

## Kelas: `CADLLMEstimator`

Kelas utama estimator. Diinisialisasi sekali saat server startup dan digunakan untuk seluruh request.

### Inisialisasi

```python
from src.llm_estimator import CADLLMEstimator

engine = CADLLMEstimator(
    api_key="YOUR_GEMINI_KEY",   # opsional, default dari env GEMINI_API_KEY
    model="gemini-2.5-flash"     # opsional, default dari env GEMINI_MODEL
)
```

### Urutan Fallback API

Setiap metode analisis menjalankan urutan fallback berikut secara otomatis:

```
1. Primary API (OpenAI-compatible proxy)
        ↓ gagal / tidak dikonfigurasi
2. Gemini SDK (genai.Client) — model loop: gemini-2.5-flash → 2.0-flash → 1.5-pro
        ↓ gagal
3. Gemini REST HTTP API langsung
        ↓ semua gagal
RuntimeError: Failed to obtain valid response
```

---

## Metode Publik

### `analyze_cad_payload(text_payload, project_name, client_name)`
Menganalisis dump teks entitas CAD dan menghasilkan WBS takeoff.

**Input**: Output dari `CADEntityExtractor.format_to_llm_payload()`
**Output**: `DynamicTakeoffResponse`

```python
result = engine.analyze_cad_payload(
    text_payload=cad_text,
    project_name="Rumah Pak Heri",
    client_name="Bpk Heri"
)
```

---

### `analyze_pdf_bytes(pdf_bytes, filename, project_name, client_name)`
Menganalisis file PDF set gambar secara multimodal page-by-page.

**Alur khusus PDF**:
1. Upload file ke Gemini Files API (`client.files.upload()`)
2. Kirim file reference ke Gemini SDK (native PDF engine)
3. Fallback ke REST API inline base64 jika SDK gagal
4. Cleanup file upload setelah selesai

---

### `analyze_image_bytes(image_bytes, filename, mime_type, project_name, client_name)`
Menganalisis gambar cetak biru arsitektur/struktur secara visual.

Mendukung: `image/jpeg`, `image/png`, `image/webp`

---

### `analyze_bim_payload(bim_payload, project_name, client_name)`
Menganalisis JSON kuantitas BIM 3D dari `BIMEntityExtractor.format_to_llm_payload()`.

---

## Metode Internal

### `_analyze_via_primary_api(...)`
Memanggil Primary OpenAI-compatible API dengan dukungan:
- Non-streaming mode (`stream: false`) → diprioritaskan
- SSE streaming fallback (`stream: true`) jika non-streaming kosong
- Kompresi gambar otomatis ke JPEG sebelum kirim sebagai base64

### `_analyze_via_rest(...)`
Memanggil Gemini REST HTTP API secara langsung (tanpa SDK). Dipakai sebagai fallback terakhir.

### `_clean_and_parse_json(text)`
Membersihkan dan memperbaiki output teks LLM mentah:

| Tahap | Aksi |
|---|---|
| 1 | Strip tag `<think>...</think>` (model reasoning DeepSeek/QwQ) |
| 2 | Ekstraksi blok ` ```json ... ``` ` |
| 3 | `json.loads()` langsung |
| 4 | Regex ekstraksi `{...}` jika parsing gagal |
| 5 | Normalisasi key alternatif (`sections`, `wbs` → `wbs_sections`) |
| 6 | Normalisasi `project_summary` jika berupa `dict` atau `list` |
| 7 | Grouping flat `items` list per `sectionCode` |

### `_get_model_candidates()`
Mengembalikan list model Gemini dalam urutan prioritas:
```
[model_utama, "gemini-2.5-flash", "gemini-2.0-flash", "gemini-1.5-pro"]
```
