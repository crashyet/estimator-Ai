"""
AHSP Mapping Engine — Advanced Semantic & Keyword Vector Search for Work Item → AHSP Code Mapping.

Uses ChromaDB (embedded vector database) + sentence-transformers with:
  1. Text cleaning & normalization (stripping WBS prefixes, "1 m3/1 m2" quantity markers, verb canonicalization)
  2. Strict unit matching & dimension penalty (m3 vs m2 vs kg)
  3. Action & Material keyword reranking
  4. Precise Cosine Similarity (S = 1 - distance)
  5. Multi-tier confidence thresholding (mapped_high, mapped_medium, unmapped)
"""

import os
import math
import re
import json
import hashlib
import logging
import time
from typing import List, Dict, Any, Optional, Tuple
from pathlib import Path

logger = logging.getLogger(__name__)

# Paths
AHSP_DIR = Path(__file__).parent
_env_excel_path = os.getenv("AHSP_EXCEL_PATH")
if _env_excel_path:
    _p = Path(_env_excel_path)
    AHSP_EXCEL_PATH = _p if _p.is_absolute() else (AHSP_DIR.parent / _env_excel_path).resolve()
else:
    AHSP_EXCEL_PATH = AHSP_DIR / "Item Pekerjaan CK.xlsx"
AHSP_VECTORDB_DIR = AHSP_DIR / "ahsp_vectordb"
AHSP_HASH_FILE = AHSP_VECTORDB_DIR / ".excel_hash"

# ChromaDB collection name
COLLECTION_NAME = "ahsp_items_ck"

# Embedding model — BAAI/bge-m3: retrieval-focused, 1024-dim, 170+ languages including Bahasa Indonesia
# Consistent with BGE reranker for end-to-end representation alignment
EMBEDDING_MODEL_NAME = "BAAI/bge-m3"
BGE_RERANKER_MODEL_NAME = "BAAI/bge-reranker-v2-m3"

# Confidence thresholds (calibrated after hybrid reranking)
THRESHOLD_HIGH = 0.65
THRESHOLD_MEDIUM = 0.50

# Number of candidates to return for medium confidence
TOP_K_CANDIDATES = 3
TOP_K_SEARCH_DEFAULT = 5
TOP_K_VECTOR_RETRIEVAL = 50  # Retrieve 50 candidates for reranking


# ─────────────────────────────────────────────────────────────────────
# Text & Unit Normalization Utilities
# ─────────────────────────────────────────────────────────────────────

ACTION_KEYWORDS = {
    "pembongkaran": ["bongkar", "pembongkaran", "demolish", "demolisi"],
    "penggalian": ["galian", "gali", "excavation", "digging", "penggalian"],
    "pengurugan": ["urugan", "urug", "timbunan", "backfill", "fill", "pengurugan"],
    "pemadatan": ["pemadatan", "padat", "compaction"],
    "pengecoran": ["pengecoran", "cor", "pouring", "casting", "ready mixed", "readymix"],
    "pemasangan": ["pemasangan", "pasang", "installation", "install", "pas."],
    "plesteran": ["plesteran", "plester", "plastering"],
    "acian": ["acian", "aci", "skimming"],
    "pengecatan": ["pengecatan", "cat", "painting", "coating"],
    "pembuatan": ["pembuatan", "buat", "fabrication", "bikin"],
    "pembersihan": ["pembersihan", "bersih", "cleaning", "clearing", "penebasan"],
    "penulangan": ["penulangan", "pembesian", "besi beton", "rebar", "wiremesh"],
    "bekisting": ["bekisting", "formwork", "cetakan"],
}

MATERIAL_KEYWORDS = {
    "beton": ["beton", "concrete", "fc"],
    "bata": ["bata", "brick", "hebel", "batako", "bata ringan"],
    "batu": ["batu kali", "batu belah", "batu gunung", "stone"],
    "keramik": ["keramik", "granit", "tile", "marmer"],
    "gypsum": ["gypsum", "gipsum", "grc", "plafon", "plafond"],
    "kayu": ["kayu", "wood", "plywood", "kaso", "papan"],
    "baja": ["baja", "steel", "wf", "hollow", "c75", "spandek", "siku", "atap baja"],
    "genteng": ["genteng", "roof tile", "atap genteng", "genting"],
    "pasir": ["pasir", "sand", "urugan pasir"],
    "aluminium": ["aluminium", "alumunium", "kusen aluminium"],
    "pipa": ["pipa", "pipe", "pvc", "ppr"],
    "cat": ["cat", "paint", "dulux", "catylac", "emulsi"],
    "sanitair": ["closet", "kloset", "wastafel", "kran", "shower", "toto", "septic tank", "resapan"],
}


def normalize_unit(unit_str: str) -> str:
    """Normalize unit string into standard category for similarity checking."""
    if not unit_str:
        return ""
    u = unit_str.strip().lower()
    
    # Volume (m3)
    if any(x in u for x in ["m3", "m³", "kubik", "mtr3"]):
        return "m3"
    # Area (m2)
    if any(x in u for x in ["m2", "m²", "persegi", "mtr2"]):
        return "m2"
    # Length (m)
    if any(x in u for x in ["m1", "m'", "meter", "m '"]):
        return "m"
    if u == "m":
        return "m"
    # Mass (kg)
    if any(x in u for x in ["kg", "kilo", "ton", "gram"]):
        return "kg"
    # Count / Unit
    if any(x in u for x in ["buah", "bh", "unit", "set", "btg", "batang", "titik", "lbr", "lembar", "pcs", "paket", "pohon", "rit"]):
        return "unit"
    # Lumpsum / Time
    if any(x in u for x in ["ls", "lot", "lumpsum", "bulan", "ruang", "hari"]):
        return "ls"
    
    return u


def clean_item_name(name: str) -> str:
    """
    Clean and normalize item name for accurate vector search & indexing.
    Removes WBS prefixes, quantity numbers ("1 m3", "1 m2"), and normalizes verbs.
    """
    if not name:
        return ""
    
    text = name.strip()
    
    # Strip leading prefix labels like "PEKERJAAN", "SEKSI", "ITEM", "BAGIAN"
    text = re.sub(r'^(?:pekerjaan|seksi|item|bagian)\s+', '', text, flags=re.IGNORECASE)
    # Strip leading WBS codes that contain at least one dot: "A.1 ", "1.2.3 ", "A.1. "
    text = re.sub(r'^(?:[a-z0-9]+\.)+[a-z0-9]*\s*[:\.-]?\s*', '', text, flags=re.IGNORECASE)
    # Strip standalone single-char/number prefix with separator: "A- ", "1: "
    text = re.sub(r'^[a-z0-9]{1,2}\s*[:\.-]\s+', '', text, flags=re.IGNORECASE)
    
    # Strip standard AHSP quantity patterns inside master names like "1 m3", "1 m2", "1 m1", "1 kg", "1 buah", "1 m"
    text = re.sub(r'\b1\s*(?:m3|m²|m2|m1|m\'|m|kg|buah|bh|set|unit|titik|ls|lbr|batang|pohon)\b', '', text, flags=re.IGNORECASE)
    
    # Remove specs notes & structural member codes in brackets like (PJ1), (K1, K2, Kp), (B1, B2, B3, BL),
    # (FP1-FP4), (J1-J4), (S1), (Lantai 1 & 2), (berdasarkan gambar), (15x20 cm), etc.
    # Pattern: any bracket content starting with 1-3 letters + digit (catches PJ1, FP1, K1, B1, J1, etc.)
    # OR starting with descriptive/specification words
    text = re.sub(
        r'\((?:'
        r'[a-z]{1,3}\d'                  # Structural codes: PJ1, FP1, K1, B1, S1, P1, J1, BL1, etc.
        r'|berdasarkan|sesuai|volume|kalkulasi|tanpa|lantai|kp'
        r'|\d+\s*[x×]\s*\d+'            # Dimension specs: (15x20 cm), (1.2x1.2m)
        r')[^)]*\)',
        '', text, flags=re.IGNORECASE
    )
    # Clean slashes with spaces (e.g. "Bata Ringan / Bata Merah" -> "Bata Ringan Bata Merah")
    text = re.sub(r'\s*/\s*', ' ', text)

    # Strip unbracketed drawing/structural member codes like "P1 & P2", "J1, J2, J3, J4", "P1, P2, P3", "FP1 s/d FP4", "B1 - B4", "K1 & K2"
    _code_tok = r'\b[a-z]{1,3}\d{1,3}\b'
    _code_list_pat = rf'{_code_tok}(?:\s*(?:[,&/-]|s/d|dan|\+)\s*{_code_tok})+'
    text = re.sub(_code_list_pat, '', text, flags=re.IGNORECASE)
    text = re.sub(rf'\s+{_code_tok}\s*$', '', text, flags=re.IGNORECASE)

    # Strip room/location qualifiers after door/window terms (e.g. "Pintu Utama" -> "Pintu", "Pintu Kamar Mandi" -> "Pintu")
    text = re.sub(r'\b(pintu|jendela)\s+(?:utama|kamar\s+mandi|km/wc|km|wc|depan|belakang|samping|balkon|teras|service)\b', r'\1', text, flags=re.IGNORECASE)

    # Strip noisy filler suffixes or prefixes like "dan pengukuran", "dan uitzet", "pengukuran dan"
    text = re.sub(r'\s+dan\s+(?:pengukuran|uitzet|perataan|pembersihan)\b', '', text, flags=re.IGNORECASE)
    text = re.sub(r'^(?:pengukuran\s+dan\s+|uitzet\s+dan\s+)', '', text, flags=re.IGNORECASE)

    # Normalize construction terms & common typos
    text = re.sub(r'\bbowplank\b', 'bouwplank', text, flags=re.IGNORECASE)
    text = re.sub(r'\buitzet\b', 'bouwplank', text, flags=re.IGNORECASE)

    # Normalize construction verbs for exact semantic alignment:
    # "galian" -> "penggalian", "urugan" -> "pengurugan", "pasang" -> "pemasangan", "cor" -> "pengecoran"
    words = text.split()
    normalized_words = []
    for w in words:
        wl = w.lower()
        if wl in ["gali", "galian"]:
            normalized_words.append("penggalian")
        elif wl in ["urug", "urugan"]:
            normalized_words.append("pengurugan")
        elif wl in ["pasang", "pas.", "pasangan"]:
            normalized_words.append("pemasangan")
        elif wl in ["cor", "readymix", "ready-mix"]:
            normalized_words.append("pengecoran")
        elif wl in ["bongkar"]:
            normalized_words.append("pembongkaran")
        elif wl in ["besi", "pembesian"]:
            normalized_words.append("penulangan")
        elif wl in ["buat", "bikin"]:
            normalized_words.append("pembuatan")
        else:
            normalized_words.append(w)
            
    text = " ".join(normalized_words)
    
    # Normalize concrete grade notation (e.g., K-300, K300, Fc 25 MPa, fc' 25)
    # Normalize spaces
    text = re.sub(r'\s+', ' ', text).strip()
    return text


def manual_keyword_search(ahsp_items: List[Any], search_query: str, limit: int = 5000) -> List[Dict[str, Any]]:
    """
    Perform multi-word keyword filtering on AHSP items.
    Matches all query terms against nama_pekerjaan or id_pekerjaan.
    """
    if not search_query:
        return [item.to_dict() if hasattr(item, "to_dict") else item for item in ahsp_items[:limit]]
    
    query_terms = [t.lower() for t in search_query.strip().split() if t.strip()]
    matched = []
    
    for item in ahsp_items:
        if isinstance(item, dict):
            nama = item.get("nama_pekerjaan", "").lower()
            code = item.get("id_pekerjaan", "").lower()
            dict_item = item
        else:
            nama = getattr(item, "nama_pekerjaan", "").lower()
            code = getattr(item, "id_pekerjaan", "").lower()
            dict_item = item.to_dict() if hasattr(item, "to_dict") else dict(item)
        
        if all(term in nama or term in code for term in query_terms):
            matched.append(dict_item)
            if len(matched) >= limit:
                break
                
    return matched


class AHSPItem:
    """Represents a single AHSP master data item."""
    __slots__ = ("id_pekerjaan", "nama_pekerjaan", "satuan")

    def __init__(self, id_pekerjaan: str, nama_pekerjaan: str, satuan: str):
        self.id_pekerjaan = id_pekerjaan
        self.nama_pekerjaan = nama_pekerjaan
        self.satuan = satuan

    def to_dict(self) -> dict:
        return {
            "id_pekerjaan": self.id_pekerjaan,
            "nama_pekerjaan": self.nama_pekerjaan,
            "satuan": self.satuan,
        }


class AHSPMapperEngine:
    """
    Advanced AHSP Mapping Engine using ChromaDB vector search & hybrid reranking.
    """

    def __init__(self):
        self._ready = False
        self._collection = None
        self._chroma_client = None
        self._embedding_fn = None
        self._bge_reranker = None
        self._bge_failed = False
        self._ahsp_items: List[AHSPItem] = []
        self._total_items = 0
        self._rerank_cache: Dict[Tuple, List[Dict[str, Any]]] = {}
        self._cohere_disabled_until = 0.0
        self._last_cohere_call_time = 0.0

    def is_ready(self) -> bool:
        """Check if the engine is initialized and ready for queries."""
        return self._ready

    def get_stats(self) -> dict:
        """Return engine statistics."""
        cohere_key = os.getenv("COHERE_API_KEY", "").strip()
        in_cooldown = time.time() < self._cohere_disabled_until
        
        active_reranker = "local_heuristic"
        if self._bge_reranker is not None:
            active_reranker = f"bge-reranker-v2-m3 ({BGE_RERANKER_MODEL_NAME})"
        elif cohere_key and not in_cooldown:
            active_reranker = f"cohere ({os.getenv('COHERE_RERANK_MODEL', 'rerank-v3.5')})"

        return {
            "ready": self._ready,
            "total_indexed_items": self._total_items,
            "embedding_model": EMBEDDING_MODEL_NAME,
            "bge_reranker_model": BGE_RERANKER_MODEL_NAME,
            "bge_reranker_loaded": self._bge_reranker is not None,
            "active_reranker": active_reranker,
            "cohere_rerank_enabled": bool(cohere_key),
            "cohere_in_cooldown": in_cooldown,
            "cache_entries": len(self._rerank_cache),
            "vector_db": "ChromaDB (embedded)",
            "collection_name": COLLECTION_NAME,
            "thresholds": {
                "high": THRESHOLD_HIGH,
                "medium": THRESHOLD_MEDIUM,
            },
            "excel_path": str(AHSP_EXCEL_PATH),
        }

    def initialize(self):
        """
        Initialize the AHSP mapping engine:
        1. Load AHSP items from Excel
        2. Check if ChromaDB index is fresh (via file hash)
        3. Build/rebuild index if needed
        """
        try:
            start_time = time.time()
            logger.info("Initializing AHSP Mapping Engine...")

            # Step 1: Load Excel
            self._ahsp_items = self._load_ahsp_from_excel()
            self._total_items = len(self._ahsp_items)
            logger.info(f"Loaded {self._total_items} AHSP items from Excel.")

            if self._total_items == 0:
                logger.warning("No AHSP items loaded from Excel. Engine will not be ready.")
                return

            # Step 2: Initialize ChromaDB client
            import chromadb
            from chromadb.config import Settings

            AHSP_VECTORDB_DIR.mkdir(parents=True, exist_ok=True)

            self._chroma_client = chromadb.PersistentClient(
                path=str(AHSP_VECTORDB_DIR),
                settings=Settings(
                    anonymized_telemetry=False,
                    allow_reset=True,
                ),
            )

            # Step 3: Setup embedding function
            from chromadb.utils.embedding_functions import SentenceTransformerEmbeddingFunction
            self._embedding_fn = SentenceTransformerEmbeddingFunction(
                model_name=EMBEDDING_MODEL_NAME,
            )

            # Step 4: Check if index needs rebuild
            current_hash = self._compute_excel_hash()
            stored_hash = self._read_stored_hash()

            if current_hash == stored_hash:
                # Index is fresh, just load existing collection
                logger.info("AHSP Vector DB index is up-to-date. Loading existing collection...")
                self._collection = self._chroma_client.get_collection(
                    name=COLLECTION_NAME,
                    embedding_function=self._embedding_fn,
                )
                count = self._collection.count()
                logger.info(f"Loaded existing ChromaDB collection with {count} items.")
            else:
                # Index is stale or missing, rebuild
                logger.info("AHSP Vector DB index is stale or missing. Building new index...")
                self._build_vector_index()
                self._write_stored_hash(current_hash)

            self._ready = True
            elapsed = time.time() - start_time
            logger.info(f"AHSP Mapping Engine initialized successfully in {elapsed:.2f}s. "
                        f"Total indexed items: {self._total_items}")

        except Exception as e:
            logger.error(f"Failed to initialize AHSP Mapping Engine: {e}", exc_info=True)
            self._ready = False

    def _load_ahsp_from_excel(self) -> List[AHSPItem]:
        """Parse the AHSP Excel file and return list of AHSPItem."""
        if not AHSP_EXCEL_PATH.exists():
            logger.error(f"AHSP Excel file not found: {AHSP_EXCEL_PATH}")
            return []

        try:
            import openpyxl
            wb = openpyxl.load_workbook(str(AHSP_EXCEL_PATH), data_only=True, read_only=True)
            ws = wb.active

            items = []
            for row in ws.iter_rows(min_row=2, values_only=True):
                id_val = row[0]
                nama_val = row[1]
                satuan_val = row[2] if len(row) > 2 else None

                if id_val is None or nama_val is None:
                    continue

                items.append(AHSPItem(
                    id_pekerjaan=str(id_val).strip(),
                    nama_pekerjaan=str(nama_val).strip(),
                    satuan=str(satuan_val).strip() if satuan_val else "",
                ))

            wb.close()
            return items

        except Exception as e:
            logger.error(f"Error loading AHSP Excel: {e}", exc_info=True)
            return []

    def _compute_excel_hash(self) -> str:
        """Compute MD5 hash of the Excel file + cleaner code version for change detection."""
        try:
            h = hashlib.md5()
            with open(AHSP_EXCEL_PATH, "rb") as f:
                for chunk in iter(lambda: f.read(8192), b""):
                    h.update(chunk)
            # Mix version tag to trigger rebuild when cleaner logic updates
            h.update(b"_v9_unbracketed_codes_risha_penalty_depth50")
            return h.hexdigest()
        except Exception:
            return ""

    def _read_stored_hash(self) -> str:
        """Read the stored hash of the last indexed Excel file."""
        try:
            if AHSP_HASH_FILE.exists():
                return AHSP_HASH_FILE.read_text().strip()
        except Exception:
            pass
        return ""

    def _write_stored_hash(self, hash_val: str):
        """Write the hash of the currently indexed Excel file."""
        try:
            AHSP_VECTORDB_DIR.mkdir(parents=True, exist_ok=True)
            AHSP_HASH_FILE.write_text(hash_val)
        except Exception as e:
            logger.warning(f"Could not write Excel hash file: {e}")

    def _build_vector_index(self):
        """
        Build the ChromaDB vector index from AHSP items.
        Deletes old collection first to handle embedding dimension changes cleanly.
        """
        # Delete old collection if it exists (required when embedding model/dimension changes)
        try:
            self._chroma_client.delete_collection(name=COLLECTION_NAME)
            logger.info(f"Deleted old ChromaDB collection '{COLLECTION_NAME}' for fresh rebuild.")
        except Exception:
            pass  # Collection didn't exist yet, that's fine

        self._collection = self._chroma_client.create_collection(
            name=COLLECTION_NAME,
            embedding_function=self._embedding_fn,
            metadata={"hnsw:space": "cosine"},
        )

        documents = []
        ids = []
        metadatas = []

        for idx, item in enumerate(self._ahsp_items):
            cleaned_nama = clean_item_name(item.nama_pekerjaan)
            doc_text = cleaned_nama
            if item.satuan:
                doc_text += f" ({item.satuan})"

            documents.append(doc_text)
            ids.append(f"ahsp-{idx}-{item.id_pekerjaan}")
            metadatas.append({
                "id_pekerjaan": item.id_pekerjaan,
                "nama_pekerjaan": item.nama_pekerjaan,
                "satuan": item.satuan,
                "index": idx,
            })

        batch_size = 500
        total = len(documents)
        for i in range(0, total, batch_size):
            end = min(i + batch_size, total)
            self._collection.add(
                documents=documents[i:end],
                ids=ids[i:end],
                metadatas=metadatas[i:end],
            )
            logger.info(f"Indexed AHSP items {i+1}-{end}/{total}")

        logger.info(f"Vector index built successfully. Total items: {self._collection.count()}")

    def _get_bge_reranker(self):
        """Lazy load local BAAI/bge-reranker-v2-m3 model once."""
        if self._bge_reranker is not None:
            return self._bge_reranker
        if self._bge_failed:
            return None

        try:
            logger.info(f"Loading local BGE Reranker model: '{BGE_RERANKER_MODEL_NAME}'...")
            from sentence_transformers import CrossEncoder
            import torch

            device = "cuda" if torch.cuda.is_available() else "cpu"
            self._bge_reranker = CrossEncoder(BGE_RERANKER_MODEL_NAME, max_length=512, device=device)
            logger.info(f"✅ BGE Reranker model '{BGE_RERANKER_MODEL_NAME}' successfully loaded on {device}.")
            return self._bge_reranker
        except Exception as e:
            logger.warning(f"BGE Reranker loading skipped/failed ({e}). Will use Cohere/Local Heuristic fallback.")
            self._bge_failed = True
            self._bge_reranker = None
            return None

    def _rerank_with_bge(
        self, query_text: str, query_unit: str, raw_candidates: List[Dict[str, Any]]
    ) -> Optional[List[Dict[str, Any]]]:
        """
        Rerank candidates locally using Hugging Face BAAI/bge-reranker-v2-m3 CrossEncoder model.
        Returns None if model fails to load or infer.
        """
        reranker = self._get_bge_reranker()
        if reranker is None or not raw_candidates:
            return None

        cleaned_query = clean_item_name(query_text)
        if not cleaned_query:
            cleaned_query = query_text

        query_unit_norm = normalize_unit(query_unit)
        query_lower = cleaned_query.lower()

        cache_key = (
            cleaned_query.lower(),
            query_unit_norm,
            "bge-m3",
            tuple(c["id_pekerjaan"] for c in raw_candidates)
        )
        if cache_key in self._rerank_cache:
            return self._rerank_cache[cache_key]

        try:
            pairs = [[cleaned_query, clean_item_name(cand["nama_pekerjaan"])] for cand in raw_candidates]
            raw_scores = reranker.predict(pairs)

            reranked = []
            for idx, raw_sc in enumerate(raw_scores):
                cand = raw_candidates[idx]
                cand_unit_norm = normalize_unit(cand.get("satuan", ""))
                cand_lower = clean_item_name(cand["nama_pekerjaan"]).lower()
                base_sim = cand.get("base_score", 0.0)

                # Convert cross-encoder logit to sigmoid probability score [0.0, 1.0]
                sig_score = 1.0 / (1.0 + math.exp(-float(raw_sc)))
                score = sig_score

                # 1. Flexible unit matching check
                if query_unit_norm and cand_unit_norm:
                    if query_unit_norm == cand_unit_norm:
                        score += 0.05  # Small bonus for exact unit match
                    else:
                        # Only apply mild penalty if BOTH are dimensional units and they mismatch (e.g. m3 vs m2, kg vs m3)
                        # Do NOT penalize discrete units (unit, buah, bh, set, ls) vs dimensional units (m2, m3, m)
                        dim_units = {"m3", "m2", "m", "kg"}
                        if query_unit_norm in dim_units and cand_unit_norm in dim_units:
                            score -= 0.12

                # 2. Local action verb match check
                q_actions = {action for action, keywords in ACTION_KEYWORDS.items() if any(k in query_lower for k in keywords)}
                c_actions = {action for action, keywords in ACTION_KEYWORDS.items() if any(k in cand_lower for k in keywords)}

                if q_actions and c_actions:
                    common_actions = q_actions.intersection(c_actions)
                    if common_actions:
                        score += 0.08  # Bonus for matching action verb
                    else:
                        has_demolish_conflict = ("pembongkaran" in q_actions and "pemasangan" in c_actions) or ("pembongkaran" in c_actions and "pemasangan" in q_actions)
                        has_dig_fill_conflict = ("penggalian" in q_actions and "pengurugan" in c_actions) or ("pengurugan" in q_actions and "penggalian" in c_actions)
                        has_clean_install_conflict = ("pembersihan" in q_actions and "pemasangan" in c_actions) or ("pembersihan" in c_actions and "pemasangan" in q_actions)

                        if has_demolish_conflict or has_dig_fill_conflict or has_clean_install_conflict:
                            score -= 0.35  # Heavy penalty for direct opposing actions
                        else:
                            score -= 0.12  # Mild penalty for non-matching actions
                elif q_actions and not c_actions:
                    score -= 0.15  # Penalty if query specifies action verb but candidate lacks it

                # 3. Local material match check
                for mat, keywords in MATERIAL_KEYWORDS.items():
                    q_has = any(k in query_lower for k in keywords)
                    c_has = any(k in cand_lower for k in keywords)
                    if q_has and c_has:
                        score += 0.10  # Bonus for matching material
                        break

                # 4. Domain Section Alignment Check (PUPR Standards)
                cand_id = str(cand.get("id_pekerjaan", "")).strip()
                civil_arch_keywords = {"pintu", "jendela", "beton", "bata", "plesteran", "acian", "bekisting", "penulangan", "atap", "kusen", "keramik", "lantai", "cat", "fondasi", "galian", "urugan"}
                q_is_civil = any(k in query_lower for k in civil_arch_keywords)

                if cand_id.startswith("5.") and q_is_civil:
                    score -= 0.25  # Heavy penalty for matching MEP/Electrical items (Section 5) to Civil/Arch queries

                if cand_id.startswith("3.11.4"):
                    hardware_words = {"engsel", "kunci", "grendel", "slot", "door closer", "door holder", "door stop", "rel", "hak angin", "kait angin"}
                    q_has_hw_word = any(hw in query_lower for hw in hardware_words)
                    if not q_has_hw_word and ("pintu" in query_lower or "jendela" in query_lower):
                        score -= 0.15  # Penalty for matching hardware fittings when query asks for main assembly

                if "risha" in cand_lower and "risha" not in query_lower:
                    score -= 0.30  # Heavy penalty for RISHA precast components when query is standard construction

                final_score = max(0.0, min(1.0, round(score, 4)))

                reranked.append({
                    "id_pekerjaan": cand["id_pekerjaan"],
                    "nama_pekerjaan": cand["nama_pekerjaan"],
                    "satuan": cand["satuan"],
                    "score": final_score,
                    "bge_score": round(sig_score, 4),
                    "base_score": base_sim,
                    "reranker": "bge_m3_hybrid"
                })

            reranked.sort(key=lambda x: x["score"], reverse=True)
            for rank, item in enumerate(reranked, 1):
                item["rank"] = rank

            logger.info(f"BGE-Reranker-v2-m3 successfully reranked {len(reranked)} candidates for query '{query_text}'.")

            if len(self._rerank_cache) > 1000:
                self._rerank_cache.clear()
            self._rerank_cache[cache_key] = reranked

            return reranked
        except Exception as e:
            logger.warning(f"BGE Reranker inference error ({e}). Falling back to Cohere/Local Heuristic.")
            return None

    def _rerank_with_cohere(
        self, query_text: str, query_unit: str, raw_candidates: List[Dict[str, Any]]
    ) -> Optional[List[Dict[str, Any]]]:
        """
        Rerank candidates using Cohere Rerank API (v2) with cache, throttling & circuit breaker.
        Returns None if API key is missing, rate-limited, or request fails.
        """
        cohere_key = os.getenv("COHERE_API_KEY", "").strip()
        if not cohere_key or not raw_candidates:
            return None

        # Check circuit breaker cooldown
        now = time.time()
        if now < self._cohere_disabled_until:
            return None  # Cohere API is in cooldown after 429 rate limit

        cleaned_query = clean_item_name(query_text)
        if not cleaned_query:
            cleaned_query = query_text

        # Check cache (exact match for query + unit + candidates)
        cache_key = (
            cleaned_query.lower(),
            query_unit.strip().lower(),
            tuple(c["id_pekerjaan"] for c in raw_candidates)
        )
        if cache_key in self._rerank_cache:
            return self._rerank_cache[cache_key]

        # Inter-request throttle: min 450ms gap between consecutive Cohere API calls to stay within free tier rate limits
        time_since_last = now - self._last_cohere_call_time
        if time_since_last < 0.45:
            time.sleep(0.45 - time_since_last)

        cohere_model = os.getenv("COHERE_RERANK_MODEL", "rerank-v3.5").strip()

        # Send pure item names to Cohere Rerank for maximum name semantic accuracy
        doc_texts = [clean_item_name(cand["nama_pekerjaan"]) for cand in raw_candidates]

        payload = {
            "model": cohere_model,
            "query": cleaned_query,
            "documents": doc_texts,
            "top_n": len(raw_candidates)
        }

        url = "https://api.cohere.com/v2/rerank"
        headers = {
            "Authorization": f"Bearer {cohere_key}",
            "Content-Type": "application/json",
            "User-Agent": "Estimator-AHSP-Reranker/2.0"
        }

        import urllib.request
        import urllib.error

        max_attempts = 2
        for attempt in range(max_attempts):
            try:
                self._last_cohere_call_time = time.time()
                req = urllib.request.Request(
                    url,
                    data=json.dumps(payload).encode("utf-8"),
                    headers=headers,
                    method="POST"
                )
                with urllib.request.urlopen(req, timeout=6.0) as resp:
                    res_data = json.loads(resp.read().decode("utf-8"))

                results = res_data.get("results", [])
                if not results:
                    logger.warning("Cohere Rerank API returned no results.")
                    return None

                query_unit_norm = normalize_unit(query_unit)
                query_lower = cleaned_query.lower()
                reranked = []

                for item in results:
                    idx = item.get("index")
                    rel_score = item.get("relevance_score", 0.0)
                    if idx is None or idx < 0 or idx >= len(raw_candidates):
                        continue

                    cand = raw_candidates[idx]
                    cand_unit_norm = normalize_unit(cand.get("satuan", ""))
                    cand_lower = clean_item_name(cand["nama_pekerjaan"]).lower()
                    base_sim = cand.get("base_score", 0.0)

                    # 1. Pure Cohere item name semantic score
                    score = rel_score

                    # 2. Flexible unit matching check
                    if query_unit_norm and cand_unit_norm:
                        if query_unit_norm == cand_unit_norm:
                            score += 0.05  # Small bonus for exact unit match
                        else:
                            dim_units = {"m3", "m2", "m", "kg"}
                            if query_unit_norm in dim_units and cand_unit_norm in dim_units:
                                score -= 0.12

                    # 3. Local action verb match check (verb logic)
                    q_actions = {action for action, keywords in ACTION_KEYWORDS.items() if any(k in query_lower for k in keywords)}
                    c_actions = {action for action, keywords in ACTION_KEYWORDS.items() if any(k in cand_lower for k in keywords)}

                    if q_actions and c_actions:
                        common_actions = q_actions.intersection(c_actions)
                        if common_actions:
                            score += 0.05  # Bonus for matching action verb
                        else:
                            has_demolish_conflict = ("pembongkaran" in q_actions and "pemasangan" in c_actions) or ("pembongkaran" in c_actions and "pemasangan" in q_actions)
                            has_dig_fill_conflict = ("penggalian" in q_actions and "pengurugan" in c_actions) or ("pengurugan" in q_actions and "penggalian" in c_actions)
                            has_clean_install_conflict = ("pembersihan" in q_actions and "pemasangan" in c_actions) or ("pembersihan" in c_actions and "pemasangan" in q_actions)

                            if has_demolish_conflict or has_dig_fill_conflict or has_clean_install_conflict:
                                score -= 0.30
                            else:
                                score -= 0.10
                    elif q_actions and not c_actions:
                        score -= 0.12

                    # 4. Domain Section Alignment Check (PUPR Standards)
                    cand_id = str(cand.get("id_pekerjaan", "")).strip()
                    civil_arch_keywords = {"pintu", "jendela", "beton", "bata", "plesteran", "acian", "bekisting", "penulangan", "atap", "kusen", "keramik", "lantai", "cat", "fondasi", "galian", "urugan"}
                    q_is_civil = any(k in query_lower for k in civil_arch_keywords)

                    if cand_id.startswith("5.") and q_is_civil:
                        score -= 0.25  # Heavy penalty for matching MEP/Electrical items (Section 5) to Civil/Arch queries

                    if cand_id.startswith("3.11.4"):
                        hardware_words = {"engsel", "kunci", "grendel", "slot", "door closer", "door holder", "door stop", "rel", "hak angin", "kait angin"}
                        q_has_hw_word = any(hw in query_lower for hw in hardware_words)
                        if not q_has_hw_word and ("pintu" in query_lower or "jendela" in query_lower):
                            score -= 0.15  # Penalty for matching hardware fittings when query asks for main assembly

                    if "risha" in cand_lower and "risha" not in query_lower:
                        score -= 0.30  # Heavy penalty for RISHA precast components when query is standard construction

                    final_score = max(0.0, min(1.0, round(score, 4)))

                    reranked.append({
                        "id_pekerjaan": cand["id_pekerjaan"],
                        "nama_pekerjaan": cand["nama_pekerjaan"],
                        "satuan": cand["satuan"],
                        "score": final_score,
                        "cohere_score": round(rel_score, 4),
                        "base_score": base_sim,
                        "reranker": "cohere_hybrid"
                    })

                reranked.sort(key=lambda x: x["score"], reverse=True)
                for rank, item in enumerate(reranked, 1):
                    item["rank"] = rank

                logger.info(f"Cohere Rerank ({cohere_model}) successfully reranked {len(reranked)} candidates for query '{query_text}'.")

                # Store in cache (cap at 1000 items)
                if len(self._rerank_cache) > 1000:
                    self._rerank_cache.clear()
                self._rerank_cache[cache_key] = reranked

                return reranked

            except urllib.error.HTTPError as http_err:
                if http_err.code == 429:
                    if attempt < max_attempts - 1:
                        logger.warning(f"Cohere Rerank API rate limited (429). Waiting 3.5s for rate limit recovery (attempt {attempt + 1}/{max_attempts})...")
                        time.sleep(3.5)
                        continue
                    else:
                        logger.warning(
                            "Cohere Rerank API rate limit (429 Too Many Requests) hit. "
                            "Activating 12s cooldown; automatically using local heuristic reranker."
                        )
                        self._cohere_disabled_until = time.time() + 12.0
                        return None
                else:
                    logger.warning(f"Cohere Rerank HTTP Error ({http_err.code}). Falling back to local heuristic reranker.")
                    return None
            except Exception as e:
                logger.warning(f"Cohere Rerank API error ({e}). Falling back to local heuristic reranker.")
                return None

        return None

    def _rerank_candidates_local(
        self, query_text: str, query_unit: str, raw_candidates: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        """
        Rerank vector search candidates using local unit matching, action & material keyword logic.
        """
        cleaned_query = clean_item_name(query_text)
        query_unit_norm = normalize_unit(query_unit)
        query_lower = cleaned_query.lower()

        reranked = []
        for cand in raw_candidates:
            base_sim = cand["base_score"]
            cand_unit_norm = normalize_unit(cand["satuan"])
            cand_lower = clean_item_name(cand["nama_pekerjaan"]).lower()

            score = base_sim

            # 1. Flexible unit matching check
            if query_unit_norm and cand_unit_norm:
                if query_unit_norm == cand_unit_norm:
                    score += 0.05  # Small bonus for exact unit match
                else:
                    dim_units = {"m3", "m2", "m", "kg"}
                    if query_unit_norm in dim_units and cand_unit_norm in dim_units:
                        score -= 0.12

            # 2. Action verb match check
            q_actions = {action for action, keywords in ACTION_KEYWORDS.items() if any(k in query_lower for k in keywords)}
            c_actions = {action for action, keywords in ACTION_KEYWORDS.items() if any(k in cand_lower for k in keywords)}

            if q_actions and c_actions:
                common_actions = q_actions.intersection(c_actions)
                if common_actions:
                    score += 0.12  # Bonus for matching action
                else:
                    has_demolish_conflict = ("pembongkaran" in q_actions and "pemasangan" in c_actions) or ("pembongkaran" in c_actions and "pemasangan" in q_actions)
                    has_dig_fill_conflict = ("penggalian" in q_actions and "pengurugan" in c_actions) or ("pengurugan" in q_actions and "penggalian" in c_actions)
                    has_clean_install_conflict = ("pembersihan" in q_actions and "pemasangan" in c_actions) or ("pembersihan" in c_actions and "pemasangan" in q_actions)

                    if has_demolish_conflict or has_dig_fill_conflict or has_clean_install_conflict:
                        score -= 0.35
                    else:
                        score -= 0.12
            elif q_actions and not c_actions:
                score -= 0.15

            # 3. Material match check
            for mat, keywords in MATERIAL_KEYWORDS.items():
                q_has = any(k in query_lower for k in keywords)
                c_has = any(k in cand_lower for k in keywords)
                if q_has and c_has:
                    score += 0.10  # Bonus for matching material
                    break

            # 4. Domain Section Alignment Check (PUPR Standards)
            cand_id = str(cand.get("id_pekerjaan", "")).strip()
            civil_arch_keywords = {"pintu", "jendela", "beton", "bata", "plesteran", "acian", "bekisting", "penulangan", "atap", "kusen", "keramik", "lantai", "cat", "fondasi", "galian", "urugan"}
            q_is_civil = any(k in query_lower for k in civil_arch_keywords)

            if cand_id.startswith("5.") and q_is_civil:
                score -= 0.25  # Heavy penalty for matching MEP/Electrical items (Section 5) to Civil/Arch queries

            if cand_id.startswith("3.11.4"):
                hardware_words = {"engsel", "kunci", "grendel", "slot", "door closer", "door holder", "door stop", "rel", "hak angin", "kait angin"}
                q_has_hw_word = any(hw in query_lower for hw in hardware_words)
                if not q_has_hw_word and ("pintu" in query_lower or "jendela" in query_lower):
                    score -= 0.15  # Penalty for matching hardware fittings when query asks for main assembly

            if "risha" in cand_lower and "risha" not in query_lower:
                score -= 0.30  # Heavy penalty for RISHA precast components when query is standard construction

            final_score = max(0.0, min(1.0, round(score, 4)))

            reranked.append({
                "id_pekerjaan": cand["id_pekerjaan"],
                "nama_pekerjaan": cand["nama_pekerjaan"],
                "satuan": cand["satuan"],
                "score": final_score,
                "base_score": base_sim,
                "reranker": "local_heuristic"
            })

        # Sort by reranked final score descending
        reranked.sort(key=lambda x: x["score"], reverse=True)

        for rank, item in enumerate(reranked, 1):
            item["rank"] = rank

        return reranked

    def _rerank_candidates(
        self, query_text: str, query_unit: str, raw_candidates: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        """
        Rerank vector search candidates using multi-tier rerankers:
        1. Local Hugging Face BAAI/bge-reranker-v2-m3 (Fast, offline, 0 API limits)
        2. Cohere Rerank API (v2)
        3. Local Heuristic Reranker (Fallback)
        """
        engine_mode = os.getenv("RERANK_ENGINE", "bge").strip().lower()

        # If user explicitly wants Cohere primary
        if engine_mode == "cohere":
            cohere_results = self._rerank_with_cohere(query_text, query_unit, raw_candidates)
            if cohere_results is not None:
                return cohere_results
            bge_results = self._rerank_with_bge(query_text, query_unit, raw_candidates)
            if bge_results is not None:
                return bge_results
        else:
            # Default: BGE-m3 primary with Cohere secondary fallback
            bge_results = self._rerank_with_bge(query_text, query_unit, raw_candidates)
            if bge_results is not None:
                return bge_results
            cohere_results = self._rerank_with_cohere(query_text, query_unit, raw_candidates)
            if cohere_results is not None:
                return cohere_results

        return self._rerank_candidates_local(query_text, query_unit, raw_candidates)

    def search(self, query: str, top_k: int = TOP_K_SEARCH_DEFAULT, item_unit: str = "") -> List[Dict[str, Any]]:
        """
        Perform semantic search & reranking for AHSP items matching the query.
        """
        if not self._ready or not self._collection:
            logger.warning("AHSP Mapper not ready. Returning empty results.")
            return []

        try:
            cleaned_q = clean_item_name(query)
            if not cleaned_q:
                cleaned_q = query

            query_text = cleaned_q
            # Only append unit to query for dimensional units (m3, m2, m, kg).
            # Discrete units (unit, buah, set, ls) are too generic and would
            # bias ChromaDB retrieval towards unrelated items (e.g. electrical accessories).
            if item_unit:
                unit_norm = normalize_unit(item_unit)
                if unit_norm in ("m3", "m2", "m", "kg"):
                    query_text += f" ({item_unit})"

            retrieval_k = max(top_k, TOP_K_VECTOR_RETRIEVAL)

            results = self._collection.query(
                query_texts=[query_text],
                n_results=min(retrieval_k, self._total_items),
                include=["metadatas", "distances"],
            )

            raw_candidates = []
            if results and results["metadatas"] and results["distances"]:
                for meta, distance in zip(results["metadatas"][0], results["distances"][0]):
                    # ChromaDB cosine distance d = 1 - cosine_similarity
                    # True cosine similarity = 1.0 - distance
                    base_similarity = max(0.0, min(1.0, 1.0 - distance))

                    raw_candidates.append({
                        "id_pekerjaan": meta["id_pekerjaan"],
                        "nama_pekerjaan": meta["nama_pekerjaan"],
                        "satuan": meta["satuan"],
                        "base_score": round(base_similarity, 4),
                    })

            # Apply hybrid reranking
            reranked = self._rerank_candidates(query, item_unit, raw_candidates)
            return reranked[:top_k]

        except Exception as e:
            logger.error(f"AHSP search error: {e}", exc_info=True)
            return []

    def map_single_item(
        self, item_name: str, item_unit: str = ""
    ) -> Dict[str, Any]:
        """
        Map a single work item name to the best matching AHSP code.
        """
        if not self._ready or not item_name or not item_name.strip():
            return {
                "ahsp_code": None,
                "ahsp_name": None,
                "ahsp_unit": None,
                "ahsp_score": None,
                "ahsp_status": "unmapped",
                "ahsp_candidates": None,
            }

        cleaned_q = clean_item_name(item_name)
        low_item = item_name.lower()
        # Guard against dummy / placeholder / diagnostic item names that cause false vector matches
        is_dummy = (
            not cleaned_q 
            or "nama tidak terdeteksi" in low_item
            or "no " in low_item and ("available" in low_item or "data" in low_item or "found" in low_item or "footing" in low_item or "column" in low_item or "beam" in low_item or "sloof" in low_item or "roof" in low_item)
            or low_item in ["derived", "estimated", "unnamed", "none", "n/a", "pekerjaan", "item", "wbs", "task"]
            or cleaned_q in ["pekerjaan", "item", "wbs", "task", "derived", "estimated"]
        )
        if is_dummy:
            return {
                "ahsp_code": None,
                "ahsp_name": None,
                "ahsp_unit": None,
                "ahsp_score": None,
                "ahsp_status": "unmapped",
                "ahsp_candidates": None,
            }

        candidates = self.search(item_name, top_k=TOP_K_CANDIDATES + 2, item_unit=item_unit)

        if not candidates:
            return {
                "ahsp_code": None,
                "ahsp_name": None,
                "ahsp_unit": None,
                "ahsp_score": None,
                "ahsp_status": "unmapped",
                "ahsp_candidates": None,
            }

        best = candidates[0]
        score = best["score"]

        if score >= THRESHOLD_HIGH:
            return {
                "ahsp_code": best["id_pekerjaan"],
                "ahsp_name": best["nama_pekerjaan"],
                "ahsp_unit": best["satuan"],
                "ahsp_score": score,
                "ahsp_status": "mapped_high",
                "ahsp_candidates": candidates[:TOP_K_CANDIDATES],
            }
        elif score >= THRESHOLD_MEDIUM:
            return {
                "ahsp_code": best["id_pekerjaan"],
                "ahsp_name": best["nama_pekerjaan"],
                "ahsp_unit": best["satuan"],
                "ahsp_score": score,
                "ahsp_status": "mapped_medium",
                "ahsp_candidates": candidates[:TOP_K_CANDIDATES],
            }
        else:
            return {
                "ahsp_code": None,
                "ahsp_name": None,
                "ahsp_unit": None,
                "ahsp_score": score,
                "ahsp_status": "unmapped",
                "ahsp_candidates": candidates[:TOP_K_CANDIDATES],
            }

    def inspect_single_item(
        self, item_name: str, item_unit: str = "", top_k: int = 5
    ) -> Dict[str, Any]:
        """
        Inspect full raw pipeline step-by-step for a single work item:
        1. Query & normalization
        2. Raw VectorDB candidates (ChromaDB cosine similarity before reranking)
        3. Raw Reranked candidates (Scores after CrossEncoder BGE-M3 / Cohere / Heuristic)
        4. Final mapping decision (mapped_high, mapped_medium, unmapped)
        """
        if not self._ready or not self._collection:
            return {"error": "AHSP Mapper Engine is not ready or not initialized."}

        cleaned_q = clean_item_name(item_name) or item_name
        query_text = cleaned_q
        # Only append unit for dimensional units (same logic as search())
        if item_unit:
            unit_norm = normalize_unit(item_unit)
            if unit_norm in ("m3", "m2", "m", "kg"):
                query_text += f" ({item_unit})"

        retrieval_k = max(top_k, TOP_K_VECTOR_RETRIEVAL)

        results = self._collection.query(
            query_texts=[query_text],
            n_results=min(retrieval_k, self._total_items),
            include=["metadatas", "distances"],
        )

        raw_vectordb = []
        if results and results["metadatas"] and results["distances"]:
            for v_rank, (meta, distance) in enumerate(zip(results["metadatas"][0], results["distances"][0]), 1):
                base_sim = max(0.0, min(1.0, 1.0 - distance))
                raw_vectordb.append({
                    "vector_rank": v_rank,
                    "id_pekerjaan": meta["id_pekerjaan"],
                    "nama_pekerjaan": meta["nama_pekerjaan"],
                    "satuan": meta["satuan"],
                    "base_score": round(base_sim, 4),
                })

        reranked = self._rerank_candidates(item_name, item_unit, raw_vectordb)
        mapping_decision = self.map_single_item(item_name, item_unit)

        # Build comparison step
        reranked_summary = []
        for r_item in reranked[:top_k]:
            b_score = r_item.get("base_score", 0.0)
            f_score = r_item.get("score", 0.0)
            delta = round(f_score - b_score, 4)
            reranked_summary.append({
                "rank": r_item.get("rank"),
                "id_pekerjaan": r_item.get("id_pekerjaan"),
                "nama_pekerjaan": r_item.get("nama_pekerjaan"),
                "satuan": r_item.get("satuan"),
                "reranked_score": f_score,
                "base_vector_score": b_score,
                "score_delta": f"+{delta}" if delta > 0 else str(delta),
                "reranker_used": r_item.get("reranker"),
            })

        return {
            "query": {
                "input_name": item_name,
                "input_unit": item_unit,
                "cleaned_name": cleaned_q,
            },
            "stats": self.get_stats(),
            "raw_vectordb_candidates": raw_vectordb[:top_k],
            "raw_reranked_candidates": reranked_summary,
            "final_mapping": mapping_decision,
        }

    def inspect_takeoff_response(self, takeoff_response) -> Dict[str, Any]:
        """
        Inspect full raw pipeline step-by-step for an entire DynamicTakeoffResponse.
        Returns a complete evaluation dictionary containing:
        - Raw AI Output (Takeoff JSON)
        - VectorDB Raw Search Output per item
        - Reranked Raw Output per item
        - Final Mapped Output & Statistics
        """
        if not self._ready:
            return {"error": "AHSP Mapper Engine not ready."}

        raw_ai_dict = takeoff_response.model_dump() if hasattr(takeoff_response, "model_dump") else takeoff_response.dict()
        
        items_inspection = []
        high_cnt = 0
        med_cnt = 0
        unmap_cnt = 0

        for sec in takeoff_response.wbs_sections:
            for item in sec.items:
                item_insp = self.inspect_single_item(item.name, item.unit or "", top_k=5)
                
                final_map = item_insp.get("final_mapping", {})
                status = final_map.get("ahsp_status", "unmapped")
                if status == "mapped_high":
                    high_cnt += 1
                elif status == "mapped_medium":
                    med_cnt += 1
                else:
                    unmap_cnt += 1

                items_inspection.append({
                    "section_code": sec.section.code,
                    "section_name": sec.section.name,
                    "item_id": item.id,
                    "ai_raw_item": {
                        "name": item.name,
                        "volume": item.volume,
                        "unit": item.unit,
                        "confidence": item.confidence,
                        "warning_note": item.warning_note,
                    },
                    "raw_vectordb_candidates": item_insp.get("raw_vectordb_candidates", []),
                    "raw_reranked_candidates": item_insp.get("raw_reranked_candidates", []),
                    "final_mapping": final_map,
                })

        total = len(items_inspection)
        return {
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
            "engine_stats": self.get_stats(),
            "summary": {
                "total_items": total,
                "mapped_high": high_cnt,
                "mapped_medium": med_cnt,
                "unmapped": unmap_cnt,
                "high_ratio": f"{(high_cnt/total*100):.1f}%" if total > 0 else "0%",
            },
            "raw_ai_output": raw_ai_dict,
            "items_pipeline_breakdown": items_inspection,
        }

    def map_takeoff_response(self, takeoff_response) -> Any:
        """
        Bulk map all work items in a DynamicTakeoffResponse to AHSP codes.
        Modifies items in-place and returns the enriched response.
        """
        if not self._ready:
            logger.warning("AHSP Mapper not ready. Skipping bulk mapping.")
            return takeoff_response

        mapped_high_count = 0
        mapped_medium_count = 0
        unmapped_count = 0

        start_time = time.time()

        for wbs_section in takeoff_response.wbs_sections:
            for item in wbs_section.items:
                mapping = self.map_single_item(item.name, item.unit)

                item.ahsp_code = mapping["ahsp_code"]
                item.ahsp_name = mapping["ahsp_name"]
                item.ahsp_unit = mapping["ahsp_unit"]
                item.ahsp_score = mapping["ahsp_score"]
                item.ahsp_status = mapping["ahsp_status"]
                item.ahsp_candidates = mapping["ahsp_candidates"]

                # Automatically assign high-confidence or medium-confidence AHSP code to item.code
                if mapping["ahsp_code"] and mapping["ahsp_status"] in ["mapped_high", "mapped_medium"]:
                    item.code = mapping["ahsp_code"]

                if mapping["ahsp_status"] == "mapped_high":
                    mapped_high_count += 1
                elif mapping["ahsp_status"] == "mapped_medium":
                    mapped_medium_count += 1
                else:
                    unmapped_count += 1

        elapsed = time.time() - start_time
        total = mapped_high_count + mapped_medium_count + unmapped_count
        logger.info(
            f"AHSP Bulk Mapping complete in {elapsed:.3f}s: "
            f"{total} items → {mapped_high_count} high, "
            f"{mapped_medium_count} medium, {unmapped_count} unmapped"
        )

        return takeoff_response

    def get_all_items(
        self, page: int = 1, limit: int = 50, search_query: str = ""
    ) -> Dict[str, Any]:
        """
        Get paginated list of all AHSP items with server-side scripting.
        Optionally filter by search query.
        """
        if search_query.strip():
            matched_items = manual_keyword_search(self._ahsp_items, search_query.strip(), limit=5000)
            total = len(matched_items)
            start = (page - 1) * limit
            end = start + limit
            paged = matched_items[start:end]
            return {
                "items": paged,
                "total": total,
                "page": page,
                "limit": limit,
                "total_pages": max(1, (total + limit - 1) // limit),
            }
        else:
            total = self._total_items
            start = (page - 1) * limit
            end = start + limit
            paged = self._ahsp_items[start:end]
            return {
                "items": [item.to_dict() for item in paged],
                "total": total,
                "page": page,
                "limit": limit,
                "total_pages": max(1, (total + limit - 1) // limit),
            }

    def reindex(self) -> Dict[str, Any]:
        """
        Force re-index the vector database from the Excel file.
        """
        try:
            start_time = time.time()
            logger.info("Force re-indexing AHSP Vector DB...")

            self._ahsp_items = self._load_ahsp_from_excel()
            self._total_items = len(self._ahsp_items)

            if self._total_items == 0:
                return {"success": False, "error": "No items loaded from Excel."}

            self._build_vector_index()

            current_hash = self._compute_excel_hash()
            self._write_stored_hash(current_hash)

            elapsed = time.time() - start_time
            return {
                "success": True,
                "total_indexed": self._total_items,
                "elapsed_seconds": round(elapsed, 2),
            }

        except Exception as e:
            logger.error(f"Re-index failed: {e}", exc_info=True)
            return {"success": False, "error": str(e)}


# Module-level singleton instance
mapper_engine = AHSPMapperEngine()


def initialize_mapper():
    """Initialize the global mapper engine."""
    global mapper_engine
    mapper_engine.initialize()
