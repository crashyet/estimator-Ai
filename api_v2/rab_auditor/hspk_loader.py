"""
hspk_loader.py — Loader Data HSPK/AHSP dari Database PostgreSQL

Mengambil data master AHSP beserta harga satuannya langsung dari database PostgreSQL
(tabel `ahsp_items`), dengan konfigurasi yang dibaca otomatis dari `backend/.env`
(atau environment variables). Menyediakan index lookup by kode dan by nama_pekerjaan
untuk digunakan oleh engine.py dalam audit kelayakan harga RAB.
"""

import os
import time
import logging
from pathlib import Path
from typing import Dict, List, Optional
import psycopg2
from psycopg2.extras import DictCursor
from dotenv import load_dotenv

logger = logging.getLogger(__name__)

_CURRENT_DIR = Path(__file__).resolve().parent
_API_V2_ROOT = _CURRENT_DIR.parent
_ENV_PATH = _API_V2_ROOT / ".env"

# Muat variabel environment dari api_v2/.env
if _ENV_PATH.is_file():
    load_dotenv(_ENV_PATH)
else:
    load_dotenv()


def get_db_config() -> dict:
    """Konfigurasi koneksi PostgreSQL langsung dari .env api_v2."""
    return {
        "host": os.getenv("DB_HOST", "aws-0-ap-south-1.pooler.supabase.com"),
        "port": int(os.getenv("DB_PORT", "5432")),
        "dbname": os.getenv("DB_NAME", "postgres"),
        "user": os.getenv("DB_USER", "postgres.mevkrbsjgwliglxrdemm"),
        "password": os.getenv("DB_PASSWORD", "Estimat0r100!@"),
        "connect_timeout": 10,
    }


class HSPKItem:
    """Representasi satu item pekerjaan HSPK/AHSP beserta harga satuan dari database."""

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
        komponen_bahan: float = 0.0,
        komponen_tenaga: float = 0.0,
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


# Cache in-memory untuk data database
_hspk_db_cache: Optional[List[HSPKItem]] = None


def load_hspk_from_db(force_refresh: bool = False) -> List[HSPKItem]:
    """
    Mengambil seluruh data master AHSP/HSPK langsung dari tabel `ahsp_items` di database PostgreSQL.
    Menggunakan kolom `harga_satuan` yang sudah tersimpan di database.

    Args:
        force_refresh: Jika True, paksa query ulang ke database.

    Returns:
        List of HSPKItem.
    """
    global _hspk_db_cache
    if _hspk_db_cache is not None and not force_refresh:
        return _hspk_db_cache

    cfg = get_db_config()
    items: List[HSPKItem] = []

    for attempt in range(1, 3):
        try:
            conn = psycopg2.connect(**cfg)
            try:
                with conn.cursor(cursor_factory=DictCursor) as cursor:
                    sql = """
                        SELECT id_pekerjaan, nama_pekerjaan, satuan, harga_satuan, sumber
                        FROM ahsp_items
                        ORDER BY id ASC
                    """
                    cursor.execute(sql)
                    rows = cursor.fetchall()

                    for row in rows:
                        kode = str(row["id_pekerjaan"] or "").strip()
                        nama = str(row["nama_pekerjaan"] or "").strip()
                        satuan = str(row["satuan"] or "").strip()
                        harga = float(row["harga_satuan"] or 0.0)
                        sumber = str(row["sumber"] or "").strip()

                        if kode or nama:
                            items.append(
                                HSPKItem(
                                    kode=kode,
                                    nama_pekerjaan=nama,
                                    jenis_pekerjaan=sumber,
                                    satuan=satuan,
                                    harga_satuan=harga,
                                    komponen_bahan=0.0,
                                    komponen_tenaga=0.0,
                                    komponen_alat=0.0,
                                )
                            )

                _hspk_db_cache = items
                logger.info(
                    f"Berhasil memuat {len(items)} item HSPK/AHSP langsung dari PostgreSQL "
                    f"({cfg['host']}:{cfg['port']}/{cfg['dbname']}.ahsp_items)."
                )
                return items
            finally:
                conn.close()

        except Exception as e:
            if attempt < 2:
                logger.warning(f"Koneksi PostgreSQL attempt {attempt} gagal ({e}), mencoba kembali...")
                time.sleep(1)
                continue
            logger.error(f"Gagal memuat AHSP dari database PostgreSQL: {e}", exc_info=True)
            return []

    return []


def load_hspk_data(
    province: str = "",
    city: str = "",
    force_refresh: bool = False,
) -> List[HSPKItem]:
    """
    Load data master HSPK/AHSP langsung dari database PostgreSQL.

    Args:
        province: Parameter kompatibilitas lokasi proyek
        city: Parameter kompatibilitas lokasi proyek
        force_refresh: Paksa pemuatan ulang dari database tanpa cache

    Returns:
        List HSPKItem yang sudah memiliki harga satuan dari database.
    """
    return load_hspk_from_db(force_refresh=force_refresh)


def build_hspk_index(items: List[HSPKItem]) -> Dict[str, HSPKItem]:
    """
    Buat index lookup by kode AHSP untuk akses O(1).

    Args:
        items: List HSPKItem dari load_hspk_data()

    Returns:
        Dict mapping kode → HSPKItem
    """
    index: Dict[str, HSPKItem] = {}
    for item in items:
        if item.kode:
            index[item.kode] = item
            clean_kode = item.kode.strip()
            if clean_kode and clean_kode not in index:
                index[clean_kode] = item
    return index


def get_available_regions() -> List[dict]:
    """
    List informasi sumber data HSPK (Database PostgreSQL).

    Returns:
        List dict berisi info database PostgreSQL.
    """
    cfg = get_db_config()
    return [
        {
            "province_folder": "database_postgresql",
            "city_file": "ahsp_items",
            "full_path": f"postgresql://{cfg['host']}:{cfg['port']}/{cfg['dbname']}/ahsp_items",
        }
    ]
