# `src/bim_parser.py` — OpenBIM IFC Parser Engine

Memroses file OpenBIM IFC (`.ifc`) dan model Autodesk Revit native (`.rvt`, `.rfa`, `.nwd`, `.nwc`) untuk mengekstrak kuantitas parametrik 3D per elemen bangunan.

---

## Alur Kerja

```
File BIM bytes (IFC / Revit)
        │
        ▼
process_bim_bytes()  ← Entry point utama
        │
        ├─ .ifc  → ifcopenshell.open() → _extract_from_model()
        │
        └─ .rvt/.nwd → process_rvt_file()
                │
                ├─ 1. APS Cloud (jika APS_CLIENT_ID dikonfigurasi)
                ├─ 2. rvt2ifc / IfcConvert (converter lokal)
                └─ 3. ValueError (instruksi ekspor manual)
        │
        ▼
_extract_quantities()  ← Baca Quantity Sets (Qto_*BaseQuantities)
        │
        ▼
Agregasi per (level, category, family, material) → List[BIMElementQuantity]
```

---

## Kelas: `BIMEntityExtractor`

Semua metode bersifat `@classmethod` — tidak perlu instansiasi.

### Atribut Kelas

```python
TARGET_CATEGORIES = [
    "IfcWall", "IfcColumn", "IfcBeam", "IfcSlab", "IfcFooting",
    "IfcDoor", "IfcWindow", "IfcStair", "IfcCovering",
    "IfcMember", "IfcRoof", "IfcBuildingElementProxy"
]
```

---

## Metode Publik

### `process_bim_bytes(file_bytes, filename)` ⭐ Entry Point
Menerima file BIM sebagai raw bytes, menulis ke temp file, lalu memanggil parser yang sesuai.

```python
quantities = BIMEntityExtractor.process_bim_bytes(
    file_bytes=uploaded_bytes,
    filename="gedung_kantor.ifc"
)
```

**Ekstensi yang didukung**:
- `.ifc` → `process_ifc_file()`
- `.rvt`, `.rfa`, `.nwd`, `.nwc`, `.skp` → `process_rvt_file()`

---

### `process_ifc_file(file_path)`
Membuka file IFC dari path lokal menggunakan `ifcopenshell.open()`.

**Raises**: `ValueError` jika file IFC tidak valid atau korup.

---

### `process_rvt_file(rvt_file_path)`
Memroses file Revit native dengan urutan fallback:

1. **Autodesk Platform Services (APS)** — jika `APS_CLIENT_ID` & `APS_CLIENT_SECRET` ada di `.env`
2. **Converter lokal** — `rvt2ifc` atau `IfcConvert` (jika tersedia di `PATH`)
3. **ValueError** — Dengan pesan instruksi ekspor manual ke IFC dari Revit

---

### `format_to_llm_payload(quantities)`
Mengonversi `List[BIMElementQuantity]` menjadi JSON string untuk dikirim ke `CADLLMEstimator.analyze_bim_payload()`.

```python
json_str = BIMEntityExtractor.format_to_llm_payload(quantities)
result = estimator.analyze_bim_payload(json_str, project_name="Gedung A")
```

---

## Metode Internal

### `_extract_from_model(model)`
Iterasi semua `TARGET_CATEGORIES`, ekstrak level/family/material dari setiap elemen, lalu agregasi per key unik `(level, category, family_name, material)`.

### `_extract_quantities(elem, category)`
Mengambil nilai volume/area/panjang dari IFC Quantity Sets:

| Quantity | Prioritas Pencarian |
|---|---|
| Volume | `NetVolume` → `GrossVolume` → `Volume` |
| Area | `NetSideArea` → `GrossSideArea` → `NetArea` → `GrossArea` → `Area` |
| Length | `Length` → `Height` → `UnconnectedHeight` → `Width` |

Qto yang dicari: `Qto_{Category}BaseQuantities`, `BaseQuantities`, lalu semua `Qto_*` yang ada.
