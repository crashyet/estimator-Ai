"""
db_loader.py — Loader Data AHSP dari Database (PostgreSQL / MySQL)

Membaca master data pekerjaan dari tabel 'ahsp_items' di database,
dan menghitung signature/hash database untuk sinkronisasi index ChromaDB.
Secara otomatis membaca driver dan kredensial dari `backend/.env`.
"""

import os
import hashlib
import logging
from pathlib import Path
from typing import List, Optional

from dotenv import load_dotenv

logger = logging.getLogger(__name__)

# Path relatif ke direktori project
_CURRENT_DIR = Path(__file__).resolve().parent
_API_V2_ROOT = _CURRENT_DIR.parent
_API_ENV_PATH = _API_V2_ROOT / ".env"

if _API_ENV_PATH.is_file():
    load_dotenv(_API_ENV_PATH)
else:
    load_dotenv()


def get_db_config() -> dict:
    """Mengambil konfigurasi koneksi database langsung dari .env api_v2."""
    driver = os.getenv("DB_DRIVER", "pgsql").lower()
    is_postgres = "postgre" in driver or "pgsql" in driver

    return {
        "driver": "postgres" if is_postgres else "mysql",
        "host": os.getenv("DB_HOST", "aws-0-ap-south-1.pooler.supabase.com" if is_postgres else "localhost"),
        "port": int(os.getenv("DB_PORT", 5432 if is_postgres else 3306)),
        "database": os.getenv("DB_NAME", "postgres" if is_postgres else "estimator"),
        "user": os.getenv("DB_USER", "postgres.mevkrbsjgwliglxrdemm" if is_postgres else "root"),
        "password": os.getenv("DB_PASSWORD", "Estimat0r100!@" if is_postgres else ""),
    }


def load_ahsp_from_db(item_class) -> List:
    """
    Mengambil seluruh data master AHSP dari tabel `ahsp_items`.
    Mendukung PostgreSQL dan MySQL.
    """
    cfg = get_db_config()
    items = []

    try:
        if cfg["driver"] == "postgres":
            import psycopg2
            from psycopg2.extras import DictCursor

            conn = psycopg2.connect(
                host=cfg["host"],
                port=cfg["port"],
                dbname=cfg["database"],
                user=cfg["user"],
                password=cfg["password"],
                connect_timeout=10,
            )
            try:
                with conn.cursor(cursor_factory=DictCursor) as cursor:
                    sql = """
                        SELECT id_pekerjaan, nama_pekerjaan, satuan
                        FROM ahsp_items
                        ORDER BY id ASC
                    """
                    cursor.execute(sql)
                    rows = cursor.fetchall()
                    for row in rows:
                        id_val = row["id_pekerjaan"]
                        nama_val = row["nama_pekerjaan"]
                        satuan_val = row["satuan"] or ""
                        if id_val and nama_val:
                            items.append(
                                item_class(
                                    id_pekerjaan=str(id_val).strip(),
                                    nama_pekerjaan=str(nama_val).strip(),
                                    satuan=str(satuan_val).strip(),
                                )
                            )
            finally:
                conn.close()
        else:
            import pymysql

            conn = pymysql.connect(
                host=cfg["host"],
                port=cfg["port"],
                database=cfg["database"],
                user=cfg["user"],
                password=cfg["password"],
                charset="utf8mb4",
                cursorclass=pymysql.cursors.DictCursor,
                connect_timeout=5,
            )
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
            finally:
                conn.close()

        logger.info(
            f"Berhasil memuat {len(items)} item AHSP dari database {cfg['driver'].upper()} "
            f"({cfg['host']}:{cfg['port']}/{cfg['database']}.ahsp_items)."
        )
        return items

    except Exception as e:
        logger.error(f"Gagal memuat AHSP dari database: {e}", exc_info=True)
        return []


def get_db_ahsp_hash() -> str:
    """
    Menghasilkan hash representasi kondisi data di tabel `ahsp_items`.
    Mendukung PostgreSQL dan MySQL.
    """
    cfg = get_db_config()
    try:
        if cfg["driver"] == "postgres":
            import psycopg2
            from psycopg2.extras import DictCursor

            conn = psycopg2.connect(
                host=cfg["host"],
                port=cfg["port"],
                dbname=cfg["database"],
                user=cfg["user"],
                password=cfg["password"],
                connect_timeout=10,
            )
            try:
                with conn.cursor(cursor_factory=DictCursor) as cursor:
                    cursor.execute("""
                        SELECT COUNT(*) AS total, COALESCE(MAX(updated_at), '1970-01-01'::timestamp) AS max_updated
                        FROM ahsp_items
                    """)
                    res = cursor.fetchone()
                    if not res or res["total"] == 0:
                        return ""
                    raw_sig = f"db_ahsp_{res['total']}_{res['max_updated']}_v1"
                    return hashlib.md5(raw_sig.encode("utf-8")).hexdigest()
            finally:
                conn.close()
        else:
            import pymysql

            conn = pymysql.connect(
                host=cfg["host"],
                port=cfg["port"],
                database=cfg["database"],
                user=cfg["user"],
                password=cfg["password"],
                charset="utf8mb4",
                cursorclass=pymysql.cursors.DictCursor,
                connect_timeout=5,
            )
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
