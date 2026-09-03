# `src/cad_parser.py` — Vector CAD File Parser Engine

Mengekstrak entitas vektor, teks anotasi, dimensi, atribut blok, dan pengukuran geometri dari file gambar teknik 2D.

---

## Format File yang Didukung

| Format | Ekstensi | Library / Metode |
|---|---|---|
| AutoCAD DWG | `.dwg`, `.dwt` | `dwg2dxf` → `ODAFileConverter` → `ezdwg` (fallback chain) |
| Drawing Exchange | `.dxf` | `ezdxf` (langsung) |
| Design Web Format | `.dwf`, `.dwfx` | ZIP+XML parsing via `zipfile` |
| Scalable Vector Graphics | `.svg` | `xml.etree.ElementTree` |
| HP Plotter Language | `.plt`, `.hpgl`, `.hpg` | Regex HPGL command parsing |

---

## Kelas: `CADEntityExtractor`

Semua metode bersifat `@staticmethod` atau `@classmethod`. Tidak perlu instansiasi.

---

## Metode Publik

### `process_file_bytes(file_bytes, filename)` ⭐ Entry Point
Menerima file CAD sebagai raw bytes, menulis ke temp file, lalu memanggil parser yang sesuai berdasarkan ekstensi.

```python
cad_data = CADEntityExtractor.process_file_bytes(
    file_bytes=uploaded_bytes,
    filename="denah_lantai.dwg"
)
payload = CADEntityExtractor.format_to_llm_payload(cad_data)
```

---

### `format_to_llm_payload(cad_data)`
Memformat hasil ekstraksi menjadi teks terstruktur multi-bagian untuk dikonsumsi LLM:

```
=== DRAWING LAYERS & VECTOR CAD ENTITIES DUMP ===

--- LAYER-WISE TEXT & ANNOTATIONS ---
[LAYER: DIMENSI]
- 12000
- 6000
...

--- BLOCK ATTRIBUTES & SCHEDULE TABLES ---
- [Block: TITLE_BLOCK | Layer: KETERANGAN] → PROJECT: Rumah Type 45

--- DIMENSION NOTATIONS & MEASUREMENTS ---
- [DIMENSI] 12.00

--- LAYER GEOMETRY MEASUREMENTS (LINEAR LENGTHS) ---
Note: CAD Indonesia biasanya menggunakan mm atau cm.
- [LAYER: DINDING] Linear Length: 120000.00 CAD units (=120.00m if mm, =1200.00m if cm) | Entities: 48
```

---

### `extract_from_dxf_file(dxf_file_path)`
Parser DXF via `ezdxf`. Mengekstrak:
- `TEXT` & `MTEXT` entities per layer
- `INSERT` (block references) dengan `ATTRIB` values
- `DIMENSION` entities dengan nilai ukuran
- `LINE` & `LWPOLYLINE`/`POLYLINE` dengan total panjang geometri

---

### `extract_from_dwg_file(dwg_file_path, timeout_seconds)`
Parser DWG dengan chain fallback otomatis:

1. **`dwg2dxf`** (sistem/project bin) — via `subprocess`
2. **`ODAFileConverter`** — jika tersedia di PATH
3. **`ezdwg.to_dxf()`** — via isolated subprocess worker (hindari memory leak)

> **Catatan**: AutoCAD 2018+ (format `AC1032`) sering menyebabkan timeout di `ezdwg`. Sarankan user simpan ulang sebagai AutoCAD 2013/2010 jika ini terjadi.

---

### `extract_from_dwf_file(dwf_file_path)`
Mengekstrak teks dari DWF/DWFX. DWF adalah format ZIP berisi XML — parser membaca semua entry `.xml`, `.txt`, `.descriptor`, `.manifest`.

---

### `extract_from_svg_file(svg_file_path)`
Mengekstrak text nodes (`<text>`, `<tspan>`) per group/layer dari SVG via `xml.etree.ElementTree`. Namespace SVG di-strip otomatis.

---

### `extract_from_plt_file(plt_file_path)`
Parser HPGL/PLT via regex. Mendukung commands:
- `LB` — Label (teks)
- `SP` — Select Pen (menentukan layer/pen number)
- `PU`/`PD` — Pen Up/Down (geometri)
- `PA`/`PR` — Plot Absolute/Relative (koordinat)

---

## Fungsi Utilitas

### `fix_ezdwg_cjk_encoding(text)`
Memperbaiki karakter CJK yang rusak dari parser `ezdwg` Rust. Karakter > `0x7F` dikonversi via `utf-16-le` → `cp1252` untuk memulihkan teks ASCII aslinya.

Contoh: `'汒'` → `'Rl'`, `'㉓'` → `'S2'`

### `get_polyline_length(poly)`
Menghitung total panjang 2D dari `LWPOLYLINE` atau `POLYLINE` menggunakan `math.hypot()`. Mendukung polyline tertutup (`is_closed`).

---

## Format Output `cad_data`

```python
{
    "layers": ["DIMENSI", "DINDING", "KETERANGAN", ...],
    "text_by_layer": {
        "DIMENSI": ["12000", "6000", "3000"],
        "KETERANGAN": ["Rumah Type 45", "Skala 1:100"]
    },
    "block_attributes": [
        {"block_name": "TITLE_BLOCK", "layer": "KETERANGAN", "attributes": {"PROJECT": "..."}}
    ],
    "dimensions": ["[DIMENSI] 12.00", "[STRUKTUR] 6.00"],
    "geometry_by_layer": {
        "DINDING": {"total_length": 120000.0, "entity_count": 48}
    },
    "total_texts_count": 35,
    "source_file_type": "DWG (AC1021 via dwg2dxf)"
}
```
