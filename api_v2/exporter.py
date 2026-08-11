import json
import logging
from typing import Dict, Any
import pandas as pd
from schemas import DynamicTakeoffResponse

logger = logging.getLogger(__name__)

def export_takeoff_to_excel(takeoff_data: DynamicTakeoffResponse, output_path: str) -> str:
    """
    Export DynamicTakeoffResponse to formatted Excel spreadsheet (.xlsx).
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
            {"Property": "Judul Proyek", "Value": takeoff_data.project.title},
            {"Property": "Nama Klien", "Value": takeoff_data.project.client},
            {"Property": "Status Proyek", "Value": takeoff_data.project.status},
            {"Property": "Ringkasan Analisis", "Value": takeoff_data.project_summary},
            {"Property": "Total Seksi WBS", "Value": len(takeoff_data.wbs_sections)},
            {"Property": "Total Item Pekerjaan", "Value": total_items}
        ]
        summary_df = pd.DataFrame(summary_rows)
        summary_df.to_excel(writer, sheet_name="Project Info", index=False)

    logger.info(f"Takeoff successfully exported to Excel: {output_path}")
    return output_path

def export_takeoff_to_json(takeoff_data: DynamicTakeoffResponse, output_path: str) -> str:
    """
    Export DynamicTakeoffResponse to JSON file.
    """
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(takeoff_data.to_frontend_format(), f, indent=2, ensure_ascii=False)
    logger.info(f"Takeoff successfully exported to JSON: {output_path}")
    return output_path
