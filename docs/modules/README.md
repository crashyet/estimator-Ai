# 📦 Dokumentasi Modul `api_v2/src/`

Setiap file di sini mendokumentasikan **satu modul** dari direktori `api_v2/src/`. Kode Python tetap bersih — hanya berisi komentar singkat satu baris dan docstring minimal. Detail lengkap ada di sini.

---

## Daftar Dokumentasi

| File Docs | Modul Python | Tanggung Jawab |
|---|---|---|
| [schemas.md](schemas.md) | `src/schemas.py` | Pydantic Data Schemas (`DynamicTakeoffResponse`, `EstimateItem`, `BIMElementQuantity`, dll.) |
| [prompts.md](prompts.md) | `src/prompts.py` | AI System Prompts & User Prompt Builders untuk CAD, PDF, Image, BIM |
| [llm_estimator.md](llm_estimator.md) | `src/llm_estimator.py` | Gemini LLM Execution Engine dengan multi-tier fallback & JSON repair |
| [bim_parser.md](bim_parser.md) | `src/bim_parser.py` | OpenBIM IFC Parser via `ifcopenshell` & orkestrasi konversi Revit |
| [cad_parser.md](cad_parser.md) | `src/cad_parser.py` | CAD Vector Parser untuk DWG/DXF/DWF/SVG/PLT |
| [aps_client_and_exporter.md](aps_client_and_exporter.md) | `src/aps_client.py` + `src/exporter.py` | Autodesk Cloud (APS) API Client & Export Engine (Excel/JSON) |
