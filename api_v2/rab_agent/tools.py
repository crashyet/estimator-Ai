"""
tools.py — Tools & Deterministic Math Engine untuk AI RAB Co-Pilot Agent

Menyediakan fungsi pencarian database AHSP (via ahsp_mapper) dan
kalkulasi matematika deterministik agar perubahan angka tidak berhalusinasi.
"""

import logging
from typing import List, Dict, Any, Optional

logger = logging.getLogger(__name__)

# Lazy reference to AHSP mapper
_mapper = None


def get_ahsp_mapper():
    """Mengambil instance singleton AHSPMapperEngine."""
    global _mapper
    if _mapper is None:
        try:
            from ahsp.ahsp_mapper import get_mapper
            _mapper = get_mapper()
        except Exception as e:
            logger.warning(f"Gagal mengambil AHSP mapper singleton: {e}")
            _mapper = None
    return _mapper


def search_ahsp_candidates(query: str, unit: str = "", top_k: int = 4) -> List[Dict[str, Any]]:
    """
    Mencari item pekerjaan resmi AHSP berdasarkan query teks dan satuan.
    Mengembalikan list kandidat: kode, nama pekerjaan, satuan, skor kesesuaian.
    """
    mapper = get_ahsp_mapper()
    if not mapper or not mapper.is_ready():
        logger.info("AHSP mapper belum ready, fallback pencarian kosong.")
        return []

    try:
        results = mapper.search(query=query, top_k=top_k, item_unit=unit)
        candidates = []
        for r in results:
            candidates.append({
                "id_pekerjaan": r.get("id_pekerjaan", ""),
                "nama_pekerjaan": r.get("nama_pekerjaan", ""),
                "satuan": r.get("satuan", ""),
                "score": r.get("score", 0.0),
            })
        return candidates
    except Exception as e:
        logger.error(f"Error saat mencari kandidat AHSP: {e}")
        return []


def calculate_action_deltas(
    actions: List[Dict[str, Any]],
    current_items_map: Dict[int, Any]
) -> float:
    """
    Menghitung selisih biaya (cost_delta) per aksi dan total cost_impact
    secara deterministik (matematika murni Python).
    """
    total_impact = 0.0

    for act in actions:
        action_type = act.get("action_type")
        changes = act.get("changes", {})
        target_id = act.get("target_item_id")
        old_item = current_items_map.get(target_id) if target_id else None

        delta = 0.0

        if action_type == "ADD_ITEM":
            vol = float(changes.get("volume", 1.0))
            price = float(changes.get("unit_price", 0.0))
            delta = vol * price

        elif action_type == "DELETE_ITEM":
            if old_item:
                old_vol = float(getattr(old_item, "volume", 0.0))
                old_price = float(getattr(old_item, "unit_price", 0.0))
                delta = - (old_vol * old_price)
                act["old_values"] = {
                    "item_name": getattr(old_item, "description", ""),
                    "volume": old_vol,
                    "unit": getattr(old_item, "unit", ""),
                    "unit_price": old_price,
                    "total_price": old_vol * old_price
                }

        elif action_type == "UPDATE_ITEM":
            if old_item:
                old_vol = float(getattr(old_item, "volume", 0.0))
                old_price = float(getattr(old_item, "unit_price", 0.0))
                old_total = old_vol * old_price

                new_vol = float(changes.get("volume", old_vol))
                new_price = float(changes.get("unit_price", old_price))
                new_total = new_vol * new_price

                delta = new_total - old_total

                act["old_values"] = {
                    "item_name": getattr(old_item, "description", ""),
                    "volume": old_vol,
                    "unit": getattr(old_item, "unit", ""),
                    "unit_price": old_price,
                    "total_price": old_total
                }

        act["cost_delta"] = round(delta, 2)
        total_impact += delta

    return round(total_impact, 2)
