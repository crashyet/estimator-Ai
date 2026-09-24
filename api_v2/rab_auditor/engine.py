"""
engine.py — Layer 1: Deterministic Validation Engine

Validasi RAB berbasis aturan pasti (tanpa LLM):
1. Pengecekan Volume Kosong / Nol → CRITICAL
2. Pengecekan Deviasi Harga Satuan vs HSPK → WARNING / CRITICAL
3. Fuzzy Matching nama item ke database HSPK via RapidFuzz

Sesuai PRD Section 6 "Layer 1: Deterministic Engine".
"""

import re
import logging
from typing import List, Optional, Tuple

from rab_auditor.schemas import RABAuditItem, AnomalyResult
from rab_auditor.hspk_loader import HSPKItem, load_hspk_data, build_hspk_index

logger = logging.getLogger(__name__)

# ─────────────────────────────────────────────────────────────────────
# THRESHOLD CONSTANTS (PRD Section 6.1)
# ─────────────────────────────────────────────────────────────────────

# Deviasi harga: Normal (wajar)
NORMAL_LOW = -15.0   # -15%
NORMAL_HIGH = 15.0   # +15%

# Warning: Overpriced ringan
WARNING_OVER_HIGH = 35.0  # +15% < Δ ≤ +35%

# Critical: Overpriced berat (indikasi markup)
# Δ > +35%

# Warning: Underpriced (risiko mutu / mangkrak)
WARNING_UNDER_LOW = -25.0  # Δ < -25%


# ─────────────────────────────────────────────────────────────────────
# FUZZY MATCHING & NORMALIZATION
# ─────────────────────────────────────────────────────────────────────

def normalize_description(text: str) -> str:
    """
    Normalisasi deskripsi pekerjaan konstruksi untuk meningkatkan akurasi matching:
    - Bersihkan sitasi peraturan resmi (e.g., 'Lihat Peraturan Menteri...')
    - Normalisasi pecahan (½ -> 1/2, ¼ -> 1/4, ¾ -> 3/4)
    - Normalisasi terminologi bata/batu (1/2 batu -> 1/2 bata, 1 batu -> 1 bata)
    - Normalisasi rasio campuran semen/pasir (1sp : 4pp -> 1sp:4pp)
    - Bersihkan tanda baca khusus
    """
    if not text:
        return ""
    t = text.lower()
    t = re.sub(r'\(lihat peraturan.*?\)', '', t, flags=re.IGNORECASE)
    t = t.replace('½', ' 1/2 ').replace('¼', ' 1/4 ').replace('¾', ' 3/4 ')
    t = re.sub(r'\btebal\s+1/2\s+batu\b', '1/2 bata', t)
    t = re.sub(r'\b1/2\s+batu\b', '1/2 bata', t)
    t = re.sub(r'\b1\s+batu\b', '1 bata', t)
    t = re.sub(r'(\d+)\s*sp\s*:\s*(\d+)\s*pp', r'\1sp:\2pp', t)
    t = re.sub(r'[^a-z0-9/:.]+', ' ', t)
    return ' '.join(t.split())


def _try_import_rapidfuzz():
    """Import rapidfuzz, return None jika tidak tersedia."""
    try:
        from rapidfuzz import fuzz, process
        return fuzz, process
    except ImportError:
        logger.warning("rapidfuzz tidak tersedia. Fuzzy matching dinonaktifkan.")
        return None, None


def find_best_hspk_match(
    item_description: str,
    item_unit: str,
    hspk_items: List[HSPKItem],
    score_threshold: float = 55.0,
) -> Optional[HSPKItem]:
    """
    Cari item HSPK yang paling cocok dengan deskripsi item RAB menggunakan fuzzy matching ter-normalisasi.

    Args:
        item_description: Uraian pekerjaan dari user
        item_unit: Satuan pekerjaan dari user
        hspk_items: List HSPK reference items
        score_threshold: Minimum similarity score (0-100)

    Returns:
        HSPKItem terbaik atau None jika tidak ada yang cocok
    """
    if not hspk_items or not item_description:
        return None

    fuzz, _ = _try_import_rapidfuzz()
    if fuzz is None:
        return _fallback_keyword_match(item_description, item_unit, hspk_items)

    norm_query = normalize_description(item_description)
    clean_unit = (item_unit or "").strip().lower()

    best_item = None
    best_score = 0.0

    for item in hspk_items:
        norm_target = normalize_description(item.nama_pekerjaan)
        score = fuzz.token_set_ratio(norm_query, norm_target)

        # Bonus kesesuaian satuan (+5 poin)
        if clean_unit and item.satuan and clean_unit == item.satuan.strip().lower():
            score += 5.0

        if score > best_score:
            best_score = score
            best_item = item

    if best_score < score_threshold or best_item is None:
        return None

    logger.debug(
        f"HSPK Match: '{item_description[:50]}' → '{best_item.nama_pekerjaan[:50]}' "
        f"(score={best_score:.1f}, kode={best_item.kode})"
    )

    return best_item


def _fallback_keyword_match(
    description: str,
    unit: str,
    hspk_items: List[HSPKItem],
) -> Optional[HSPKItem]:
    """Fallback keyword matching jika rapidfuzz tidak tersedia."""
    desc_norm = normalize_description(description)
    keywords = [k for k in desc_norm.split() if len(k) >= 3]

    best_item = None
    best_count = 0

    for item in hspk_items:
        cand_norm = normalize_description(item.nama_pekerjaan)
        count = sum(1 for kw in keywords if kw in cand_norm)
        if count > best_count:
            best_count = count
            best_item = item

    # Minimum 2 keyword match
    if best_count >= 2:
        return best_item
    return None


# ─────────────────────────────────────────────────────────────────────
# CORE VALIDATION FUNCTIONS
# ─────────────────────────────────────────────────────────────────────

def check_volume_zero(item: RABAuditItem) -> Optional[AnomalyResult]:
    """
    Pengecekan Volume Kosong / Nol.
    PRD: Jika volume == 0.00 atau null pada item berbayar → CRITICAL.
    """
    if item.volume is None or item.volume == 0.0:
        return AnomalyResult(
            item_id=item.id,
            type="VOLUME_ZERO",
            severity="CRITICAL",
            field="volume",
            message=f"Volume pekerjaan bernilai 0.00 {item.unit} pada item \"{item.description[:60]}\".",
            recommendation=f"Isi estimasi volume sesuai dimensi aktual proyek. "
                          f"Pastikan rumus luas/volume telah dihitung dengan benar.",
            current_value=0.0,
        )
    return None


def check_price_deviation(
    item: RABAuditItem,
    hspk_items: List[HSPKItem],
    hspk_index: dict,
) -> Optional[AnomalyResult]:
    """
    Pengecekan Deviasi Harga Satuan vs HSPK.

    Alur:
    1. Coba lookup by kode AHSP dulu (exact match).
    2. Jika tidak ada, fuzzy match by nama pekerjaan.
    3. Hitung deviasi dan klasifikasikan sesuai threshold PRD.
    """
    if item.unit_price <= 0:
        return None

    # Step 1: Exact match by kode AHSP
    benchmark_item = None
    if item.ahsp_code and item.ahsp_code in hspk_index:
        benchmark_item = hspk_index[item.ahsp_code]

    # Step 2: Fuzzy match by nama
    if benchmark_item is None:
        benchmark_item = find_best_hspk_match(
            item.description, item.unit, hspk_items
        )

    if benchmark_item is None or benchmark_item.harga_satuan <= 0:
        return None  # Tidak bisa membandingkan

    # Step 3: Hitung deviasi
    benchmark_price = benchmark_item.harga_satuan
    deviation = ((item.unit_price - benchmark_price) / benchmark_price) * 100.0
    deviation = round(deviation, 1)

    # Step 4: Klasifikasi sesuai threshold PRD
    if NORMAL_LOW <= deviation <= NORMAL_HIGH:
        return None  # Normal, tidak ada anomali

    if deviation > WARNING_OVER_HIGH:
        # CRITICAL: Overpriced berat
        return AnomalyResult(
            item_id=item.id,
            type="PRICE_OVERPRICED",
            severity="CRITICAL",
            field="unit_price",
            message=(
                f"Harga satuan lebih tinggi {deviation:+.1f}% dari standar HSPK "
                f"({benchmark_item.kode}: {benchmark_item.nama_pekerjaan[:50]})."
            ),
            recommendation=(
                f"Harga acuan HSPK: Rp {benchmark_price:,.0f} / {benchmark_item.satuan}. "
                f"Pertimbangkan harga di rentang wajar (±15% dari acuan)."
            ),
            current_value=item.unit_price,
            benchmark_value=benchmark_price,
            deviation_percent=deviation,
        )

    if deviation > NORMAL_HIGH:
        # WARNING: Overpriced ringan
        return AnomalyResult(
            item_id=item.id,
            type="PRICE_OVERPRICED",
            severity="WARNING",
            field="unit_price",
            message=(
                f"Harga satuan lebih tinggi {deviation:+.1f}% dari standar HSPK "
                f"({benchmark_item.kode}: {benchmark_item.nama_pekerjaan[:50]})."
            ),
            recommendation=(
                f"Harga acuan HSPK: Rp {benchmark_price:,.0f} / {benchmark_item.satuan}. "
                f"Harga masih wajar namun perlu ditinjau."
            ),
            current_value=item.unit_price,
            benchmark_value=benchmark_price,
            deviation_percent=deviation,
        )

    if deviation < WARNING_UNDER_LOW:
        # WARNING: Underpriced
        return AnomalyResult(
            item_id=item.id,
            type="PRICE_UNDERPRICED",
            severity="WARNING",
            field="unit_price",
            message=(
                f"Harga satuan lebih rendah {deviation:+.1f}% dari standar HSPK "
                f"({benchmark_item.kode}: {benchmark_item.nama_pekerjaan[:50]})."
            ),
            recommendation=(
                f"Harga acuan HSPK: Rp {benchmark_price:,.0f} / {benchmark_item.satuan}. "
                f"Harga terlalu rendah berisiko material tidak sesuai spesifikasi atau proyek mangkrak."
            ),
            current_value=item.unit_price,
            benchmark_value=benchmark_price,
            deviation_percent=deviation,
        )

    return None


# ─────────────────────────────────────────────────────────────────────
# MAIN ENGINE RUNNER
# ─────────────────────────────────────────────────────────────────────

def run_deterministic_audit(
    items: List[RABAuditItem],
    province: str,
    city: str,
) -> List[AnomalyResult]:
    """
    Jalankan seluruh validasi Layer 1 (Deterministic) pada list item RAB.

    Args:
        items: Daftar item pekerjaan RAB
        province: Nama provinsi untuk lookup HSPK
        city: Nama kabupaten/kota untuk lookup HSPK

    Returns:
        List AnomalyResult berisi semua temuan anomali
    """
    anomalies: List[AnomalyResult] = []

    # Load HSPK reference data
    hspk_items = load_hspk_data(province, city)
    hspk_index = build_hspk_index(hspk_items) if hspk_items else {}

    if not hspk_items:
        logger.warning(
            f"Data HSPK tidak tersedia untuk {province}/{city}. "
            f"Hanya volume check yang akan dijalankan."
        )

    for item in items:
        # Check 1: Volume Zero
        vol_anomaly = check_volume_zero(item)
        if vol_anomaly:
            anomalies.append(vol_anomaly)

        # Check 2: Price Deviation (hanya jika HSPK tersedia)
        if hspk_items:
            price_anomaly = check_price_deviation(item, hspk_items, hspk_index)
            if price_anomaly:
                anomalies.append(price_anomaly)

    logger.info(
        f"Deterministic audit selesai: {len(items)} items diperiksa, "
        f"{len(anomalies)} anomali ditemukan."
    )
    return anomalies
