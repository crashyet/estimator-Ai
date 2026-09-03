"""
src/exporter.py — Export Engine untuk Output RAB / Takeoff

Menyediakan fungsi utilitas untuk mengekspor hasil analisis AI takeoff
(DynamicTakeoffResponse) ke format file eksternal:
  - Excel (.xlsx) dengan dua sheet: RAB Volume Takeoff + Project Info
  - JSON  (.json) menggunakan format flat yang kompatibel dengan frontend
"""

import json
import logging
from typing import Dict, Any

import pandas as pd

from src.schemas import DynamicTakeoffResponse

logger = logging.getLogger(__name__)


def export_takeoff_to_excel(takeoff_data: DynamicTakeoffResponse, output_path: str) -> str:
    """
    Mengekspor hasil analisis AI takeoff ke file Excel terformat (.xlsx).

    Membuat dua sheet pada satu workbook:
      - "RAB Volume Takeoff": Tabel flat berisi seluruh item pekerjaan dari semua seksi WBS,
        lengkap dengan kode seksi, nama pekerjaan, volume, satuan, dan catatan rumus AI.
      - "Project Info": Metadata proyek (judul, klien, status, ringkasan, jumlah seksi & item).

    Args:
        takeoff_data (DynamicTakeoffResponse): Objek hasil analisis AI takeoff.
        output_path (str): Path file output Excel, contoh: '/tmp/rab_export.xlsx'.

    Returns:
        str: Path file output yang sama (untuk konfirmasi ke caller).

    Raises:
        Exception: Jika terjadi kegagalan penulisan file Excel (disk penuh, path tidak valid, dsb.).
    """
    rows = []
    total_items = 0

    for wbs in takeoff_data.wbs_sections:
        sec_name = wbs.section.name
        sec_code = wbs.section.code
        for item in wbs.items:
            total_items += 1
            rows.append({
                "Kode Seksi": sec_code,
                "Seksi WBS": sec_name,
                "No": item.no,
                "Kode Item": item.code,
                "Nama Pekerjaan": item.name,
                "Volume": item.volume,
                "Satuan": item.unit,
                "Catatan / Rumus AI": item.warning_note or ""
            })

    df = pd.DataFrame(rows)

    with pd.ExcelWriter(output_path, engine="openpyxl") as writer:
        df.to_excel(writer, sheet_name="RAB Volume Takeoff", index=False)

        summary_rows = [
            {"Property": "Judul Proyek",       "Value": takeoff_data.project.title},
            {"Property": "Nama Klien",          "Value": takeoff_data.project.client},
            {"Property": "Status Proyek",       "Value": takeoff_data.project.status},
            {"Property": "Ringkasan Analisis",  "Value": takeoff_data.project_summary},
            {"Property": "Total Seksi WBS",     "Value": len(takeoff_data.wbs_sections)},
            {"Property": "Total Item Pekerjaan","Value": total_items}
        ]
        summary_df = pd.DataFrame(summary_rows)
        summary_df.to_excel(writer, sheet_name="Project Info", index=False)

    logger.info(f"Takeoff successfully exported to Excel: {output_path}")
    return output_path


def export_takeoff_to_json(takeoff_data: DynamicTakeoffResponse, output_path: str) -> str:
    """
    Mengekspor hasil analisis AI takeoff ke file JSON (.json).

    Menggunakan format flat `to_frontend_format()` agar output JSON kompatibel
    langsung dengan struktur data yang digunakan oleh React frontend (Anggaran.jsx).

    Args:
        takeoff_data (DynamicTakeoffResponse): Objek hasil analisis AI takeoff.
        output_path (str): Path file output JSON, contoh: '/tmp/rab_export.json'.

    Returns:
        str: Path file output yang sama (untuk konfirmasi ke caller).

    Raises:
        Exception: Jika terjadi kegagalan penulisan file JSON.
    """
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(takeoff_data.to_frontend_format(), f, indent=2, ensure_ascii=False)
    logger.info(f"Takeoff successfully exported to JSON: {output_path}")
    return output_path
