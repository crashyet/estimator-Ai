"""
db_loader.py — Loader Data AHSP langsung dari Database MySQL

Menghubungkan ke tabel 'ahsp_items' di MySQL, membaca master data pekerjaan,
dan menghitung signature/hash database untuk sinkronisasi index ChromaDB.
"""

import os
import hashlib
import logging
from typing import List, Optional
import pymysql

logger = logging.getLogger(__name__)


def get_db_config() -> dict:
    return {
        "host": os.getenv("DB_HOST", "localhost"),
        "port": int(os.getenv("DB_PORT", "3306")),
        "user": os.getenv("DB_USER", "root"),
        "password": os.getenv("DB_PASSWORD", "Adhitya123//"),
        "database": os.getenv("DB_NAME", "estimator"),
        "charset": "utf8mb4",
        "cursorclass": pymysql.cursors.DictCursor,
        "connect_timeout": 5,
    }


def load_ahsp_from_db(item_class) -> List:
    """
    Mengambil seluruh data master AHSP dari tabel `ahsp_items` di MySQL.
    Mengembalikan list of AHSPItem.
    """
    cfg = get_db_config()
    items = []

    try:
        conn = pymysql.connect(**cfg)
        try:
            with conn.cursor() as cursor:
                sql = """
                    SELECT id_pekerjaan, nama_pekerjaan, satuan
                    FROM ahsp_items
                    ORDER BY id ASC
                """
                cursor.execute(sql)
                rows = cursor.fetchall()

                for row in rows:
                    id_val = row.get("id_pekerjaan")
                    nama_val = row.get("nama_pekerjaan")
                    satuan_val = row.get("satuan") or ""

                    if id_val and nama_val:
                        items.append(
                            item_class(
                                id_pekerjaan=str(id_val).strip(),
                                nama_pekerjaan=str(nama_val).strip(),
                                satuan=str(satuan_val).strip(),
                            )
                        )

            logger.info(f"Berhasil memuat {len(items)} item AHSP langsung dari database MySQL ({cfg['database']}.ahsp_items).")
            return items
        finally:
            conn.close()

    except Exception as e:
        logger.error(f"Gagal memuat AHSP dari database MySQL: {e}", exc_info=True)
        return []


def get_db_ahsp_hash() -> str:
    """
    Menghasilkan hash representasi kondisi data di tabel `ahsp_items`.
    Menggunakan count + max(updated_at) agar jika ada data baru di DB,
    ChromaDB otomatis mengetahui perlunya re-index.
    """
    cfg = get_db_config()
    try:
        conn = pymysql.connect(**cfg)
        try:
            with conn.cursor() as cursor:
                cursor.execute("""
                    SELECT COUNT(*) AS total, COALESCE(MAX(updated_at), '1970-01-01') AS max_updated
                    FROM ahsp_items
                """)
                res = cursor.fetchone()
                if not res or res["total"] == 0:
                    return ""

                raw_sig = f"db_ahsp_{res['total']}_{res['max_updated']}_v1"
                return hashlib.md5(raw_sig.encode("utf-8")).hexdigest()
        finally:
            conn.close()
    except Exception as e:
        logger.warning(f"Gagal mendapatkan hash status AHSP dari database: {e}")
        return ""
