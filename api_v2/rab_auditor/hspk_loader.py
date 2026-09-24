"""
hspk_loader.py — Loader & Parser Data HSPK dari File JSON

Membaca file JSON HSPK dari folder hspk/{province}/{city}.json,
menghitung harga satuan pekerjaan (Σ koefisien × harga), dan menyediakan
index lookup by kode dan by nama_pekerjaan untuk digunakan oleh engine.py.

Struktur data HSPK per item:
{
    "kode": "1.1.1.1",
    "nama_pekerjaan": "Pembuatan 1 m' pagar sementara...",
    "jenis_pekerjaan": "PEKERJAAN PERSIAPAN",
    "satuan": "m'",
    "list": [
        { "koefisien": 0.6, "harga": 126000, "jenis_uraian": "tenaga", ... },
        { "koefisien": 0.012, "harga": 6500000, "jenis_uraian": "bahan", ... }
    ]
}
"""

import os
import json
import logging
from pathlib import Path
from typing import Dict, List, Optional, Tuple

logger = logging.getLogger(__name__)

# Path relatif dari api_v2/ ke folder hspk/
_PROJECT_ROOT = Path(__file__).resolve().parent.parent.parent  # estimator/
_HSPK_ROOT = _PROJECT_ROOT / "hspk"

# Mapping nama provinsi → nama folder
PROVINCE_FOLDER_MAP = {
    "jawa tengah": "jateng",
    "jateng": "jateng",
    "jawa timur": "jatim",
    "jatim": "jatim",
}

# Mapping nama kota/kabupaten → nama file (tanpa .json)
CITY_FILE_MAP = {
    "banyumas": "banyumas",
    "kabupaten banyumas": "banyumas",
    "kab. banyumas": "banyumas",
    "kab banyumas": "banyumas",
    "cilacap": "cilacap",
    "kabupaten cilacap": "cilacap",
    "kab. cilacap": "cilacap",
    "kab cilacap": "cilacap",
    "purbalingga": "purbalingga",
    "kabupaten purbalingga": "purbalingga",
    "kab. purbalingga": "purbalingga",
    "kab purbalingga": "purbalingga",
}


class HSPKItem:
    """Representasi satu item pekerjaan HSPK beserta harga satuan yang sudah dihitung."""

    __slots__ = (
        "kode", "nama_pekerjaan", "jenis_pekerjaan", "satuan",
        "harga_satuan", "komponen_bahan", "komponen_tenaga", "komponen_alat",
    )

    def __init__(
        self,
        kode: str,
        nama_pekerjaan: str,
        jenis_pekerjaan: str,
        satuan: str,
        harga_satuan: float,
        komponen_bahan: float,
        komponen_tenaga: float,
        komponen_alat: float = 0.0,
    ):
        self.kode = kode
        self.nama_pekerjaan = nama_pekerjaan
        self.jenis_pekerjaan = jenis_pekerjaan
        self.satuan = satuan
        self.harga_satuan = harga_satuan
        self.komponen_bahan = komponen_bahan
        self.komponen_tenaga = komponen_tenaga
        self.komponen_alat = komponen_alat

    def to_dict(self) -> dict:
        return {
            "kode": self.kode,
            "nama_pekerjaan": self.nama_pekerjaan,
            "jenis_pekerjaan": self.jenis_pekerjaan,
            "satuan": self.satuan,
            "harga_satuan": self.harga_satuan,
            "komponen_bahan": self.komponen_bahan,
            "komponen_tenaga": self.komponen_tenaga,
            "komponen_alat": self.komponen_alat,
        }


def _calculate_unit_price(item_data: dict) -> Tuple[float, float, float, float]:
    """
    Hitung harga satuan dari sub-item koefisien × harga.

    Returns:
        (harga_satuan_total, komponen_bahan, komponen_tenaga, komponen_alat)
    """
    total = 0.0
    bahan = 0.0
    tenaga = 0.0
    alat = 0.0

    for sub in item_data.get("list", []):
        koef = sub.get("koefisien", 0) or 0
        harga = sub.get("harga", 0) or 0
        subtotal = float(koef) * float(harga)
        total += subtotal

        jenis = (sub.get("jenis_uraian", "") or "").lower()
        if jenis == "bahan":
            bahan += subtotal
        elif jenis == "tenaga":
            tenaga += subtotal
        elif jenis in ("alat", "peralatan"):
            alat += subtotal

    return round(total, 2), round(bahan, 2), round(tenaga, 2), round(alat, 2)


def _resolve_hspk_path(province: str, city: str) -> Optional[Path]:
    """
    Resolve path file HSPK berdasarkan nama provinsi dan kota/kabupaten.
    Mendukung berbagai format penamaan lokasi:
    - Dengan atau tanpa awalan 'Kabupaten', 'Kab.', 'Kota'
    - Pencarian langsung ke folder provinsi atau fallback scanning ke seluruh subfolder hspk/
    Returns None jika file tidak ditemukan.
    """
    prov_key = (province or "").strip().lower()
    city_key = (city or "").strip().lower()

    if not city_key and not prov_key:
        return None

    # Normalisasi nama kota/kabupaten
    city_clean = (
        city_key.replace("kabupaten ", "")
        .replace("kab. ", "")
        .replace("kab ", "")
        .replace("kota ", "")
        .strip()
    )

    city_file = CITY_FILE_MAP.get(city_key) or CITY_FILE_MAP.get(city_clean) or city_clean

    # 1. Coba lookup jika provinsi diketahui
    if prov_key:
        prov_folder = PROVINCE_FOLDER_MAP.get(prov_key)
        if not prov_folder:
            candidate = _HSPK_ROOT / prov_key.replace(" ", "_")
            if candidate.is_dir():
                prov_folder = prov_key.replace(" ", "_")

        if prov_folder:
            path = _HSPK_ROOT / prov_folder / f"{city_file}.json"
            if path.is_file():
                return path

    # 2. Fallback: Cari nama kota di seluruh folder provinsi yang ada di hspk/
    if _HSPK_ROOT.is_dir():
        for prov_dir in sorted(_HSPK_ROOT.iterdir()):
            if not prov_dir.is_dir() or prov_dir.name.startswith("."):
                continue
            path = prov_dir / f"{city_file}.json"
            if path.is_file():
                return path

    logger.warning(f"File HSPK tidak ditemukan untuk kota='{city}', prov='{province}'")
    return None


# In-memory cache: key = path string, value = list[HSPKItem]
_hspk_cache: Dict[str, List[HSPKItem]] = {}


def load_hspk_data(province: str, city: str) -> List[HSPKItem]:
    """
    Load dan parse data HSPK untuk provinsi/kota tertentu.
    Hasilnya di-cache di memory untuk performa.

    Args:
        province: Nama provinsi (case-insensitive)
        city: Nama kabupaten/kota (case-insensitive)

    Returns:
        List HSPKItem yang sudah dihitung harga satuannya.
        Mengembalikan list kosong jika data tidak tersedia.
    """
    path = _resolve_hspk_path(province, city)
    if path is None:
        return []

    path_key = str(path)
    if path_key in _hspk_cache:
        return _hspk_cache[path_key]

    logger.info(f"Loading HSPK data from: {path}")
    try:
        with open(path, "r", encoding="utf-8") as f:
            raw = json.load(f)
    except (json.JSONDecodeError, OSError) as e:
        logger.error(f"Gagal membaca file HSPK {path}: {e}")
        return []

    items_raw = raw.get("data", [])
    items: List[HSPKItem] = []

    for item_data in items_raw:
        if item_data.get("deleted", 0) == 1:
            continue

        harga_satuan, bahan, tenaga, alat = _calculate_unit_price(item_data)

        items.append(HSPKItem(
            kode=item_data.get("kode", ""),
            nama_pekerjaan=item_data.get("nama_pekerjaan", ""),
            jenis_pekerjaan=item_data.get("jenis_pekerjaan", ""),
            satuan=item_data.get("satuan", ""),
            harga_satuan=harga_satuan,
            komponen_bahan=bahan,
            komponen_tenaga=tenaga,
            komponen_alat=alat,
        ))

    _hspk_cache[path_key] = items
    logger.info(f"Loaded {len(items)} HSPK items dari {path.name}")
    return items


def build_hspk_index(items: List[HSPKItem]) -> Dict[str, HSPKItem]:
    """
    Buat index lookup by kode AHSP untuk akses O(1).

    Args:
        items: List HSPKItem dari load_hspk_data()

    Returns:
        Dict mapping kode → HSPKItem
    """
    return {item.kode: item for item in items if item.kode}


def get_available_regions() -> List[dict]:
    """
    List semua region (provinsi/kota) yang tersedia di database HSPK.

    Returns:
        List dict dengan keys: province_folder, city_file, full_path
    """
    regions = []
    if not _HSPK_ROOT.is_dir():
        return regions

    for prov_dir in sorted(_HSPK_ROOT.iterdir()):
        if not prov_dir.is_dir() or prov_dir.name.startswith("."):
            continue
        for city_file in sorted(prov_dir.glob("*.json")):
            regions.append({
                "province_folder": prov_dir.name,
                "city_file": city_file.stem,
                "full_path": str(city_file),
            })

    return regions
