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
# Ensure offline mode so HuggingFace hub requests do not hang when network is unavailable
os.environ["HF_HUB_OFFLINE"] = "1"
os.environ["TRANSFORMERS_OFFLINE"] = "1"

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
AHSP_EXCEL_PATH = AHSP_DIR / "Item Pekerjaan CK.xlsx"
AHSP_VECTORDB_DIR = AHSP_DIR / "ahsp_vectordb"
AHSP_HASH_FILE = AHSP_VECTORDB_DIR / ".excel_hash"

# ChromaDB collection name
COLLECTION_NAME = "ahsp_items_ck"

# Embedding model — multilingual, supports Bahasa Indonesia
EMBEDDING_MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"

# Confidence thresholds (calibrated after reranking)
THRESHOLD_HIGH = 0.85
THRESHOLD_MEDIUM = 0.65

# Number of candidates to return for medium confidence
TOP_K_CANDIDATES = 3
TOP_K_SEARCH_DEFAULT = 5
TOP_K_VECTOR_RETRIEVAL = 20  # Retrieve 20 candidates for reranking


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
    "pembersihan": ["pembersihan", "bersih", "cleaning", "clearing"],
    "penulangan": ["penulangan", "pembesian", "besi beton", "rebar", "wiremesh"],
    "bekisting": ["bekisting", "formwork", "cetakan"],
}

MATERIAL_KEYWORDS = {
    "beton": ["beton", "concrete"],
    "bata": ["bata", "brick", "hebel", "batako", "bata ringan"],
    "batu": ["batu kali", "batu belah", "batu gunung", "stone"],
    "keramik": ["keramik", "granit", "homogeneous tile", "tile", "marmer"],
    "gypsum": ["gypsum", "gipsum", "grc", "plafon"],
    "kayu": ["kayu", "wood", "plywood", "kaso", "papan"],
    "baja": ["baja", "steel", "wf", "hollow", "c75", "spandek", "siku"],
    "pipa": ["pipa", "pipe", "pvc", "ppr"],
    "cat": ["cat", "paint", "dulux", "catylac", "emulsi"],
    "sanitair": ["closet", "kloset", "wastafel", "kran", "shower", "toto"],
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
    
    # Strip leading WBS codes or numbering like "A.1 ", "1.2 ", "A.1. ", "1.", "PEKERJAAN 1 - "
    text = re.sub(r'^(?:pekerjaan|seksi|item|bagian)?\s*(?:[a-z0-9]+\.)*[a-z0-9]+\s*[:\.-]?\s*', '', text, flags=re.IGNORECASE)
    
    # Strip standard AHSP quantity patterns inside master names like "1 m3", "1 m2", "1 m1", "1 kg", "1 buah", "1 m"
    text = re.sub(r'\b1\s*(?:m3|m²|m2|m1|m\'|m|kg|buah|bh|set|unit|titik|ls|lbr|batang|pohon)\b', '', text, flags=re.IGNORECASE)
    
    # Remove specs notes in brackets if purely explanatory like (berdasarkan gambar), (1 kali)
    text = re.sub(r'\((?:berdasarkan|sesuai|volume|kalkulasi|tanpa)[^)]*\)', '', text, flags=re.IGNORECASE)
    
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
        elif wl in ["pasang", "pas."]:
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
    text = re.sub(r'\bf[c\']\s*(\d+)\s*(?:mpa)?\b', r'fc \1 mpa', text, flags=re.IGNORECASE)
    text = re.sub(r'\bk\s*-\s*(\d+)\b', r'k-\1', text, flags=re.IGNORECASE)
    text = re.sub(r'\bk(\d{3})\b', r'k-\1', text, flags=re.IGNORECASE)
    
    # Normalize spaces
    text = re.sub(r'\s+', ' ', text).strip()
    return text


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
        self._ahsp_items: List[AHSPItem] = []
        self._total_items = 0

    def is_ready(self) -> bool:
        """Check if the engine is initialized and ready for queries."""
        return self._ready

    def get_stats(self) -> dict:
        """Return engine statistics."""
        return {
            "ready": self._ready,
            "total_indexed_items": self._total_items,
            "embedding_model": EMBEDDING_MODEL_NAME,
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
            h.update(b"_v3_verb_canonicalizer")
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
        Build the ChromaDB vector index from AHSP items using get_or_create_collection.
        """
        self._collection = self._chroma_client.get_or_create_collection(
            name=COLLECTION_NAME,
            embedding_function=self._embedding_fn,
            metadata={"hnsw:space": "cosine"},
        )

        # Clear existing items safely if any
        try:
            count = self._collection.count()
            if count > 0:
                existing_data = self._collection.get()
                if existing_data and existing_data.get("ids"):
                    self._collection.delete(ids=existing_data["ids"])
                    logger.info(f"Cleared {len(existing_data['ids'])} existing items from collection.")
        except Exception as e:
            logger.warning(f"Note on clearing collection: {e}")

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

    def _rerank_candidates(
        self, query_text: str, query_unit: str, raw_candidates: List[Dict[str, Any]]
    ) -> List[Dict[str, Any]]:
        """
        Rerank vector search candidates using unit matching, action & material keyword logic.
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

            # 1. Unit matching bonus / penalty
            if query_unit_norm and cand_unit_norm:
                if query_unit_norm == cand_unit_norm:
                    score += 0.08  # Bonus for exact unit match
                else:
                    # Mismatched dimension (e.g. m3 vs m2, kg vs m3)
                    if query_unit_norm in ["m3", "m2", "m", "kg"] and cand_unit_norm in ["m3", "m2", "m", "kg"]:
                        score -= 0.35  # Heavy penalty
                    else:
                        score -= 0.20

            # 2. Action verb match check
            for action, keywords in ACTION_KEYWORDS.items():
                q_has = any(k in query_lower for k in keywords)
                c_has = any(k in cand_lower for k in keywords)
                if q_has and c_has:
                    score += 0.12  # Bonus for matching action
                    break
                elif q_has and not c_has:
                    score -= 0.15  # Penalty if query specifies action but candidate lacks it
                    break

            # 3. Material match check
            for mat, keywords in MATERIAL_KEYWORDS.items():
                q_has = any(k in query_lower for k in keywords)
                c_has = any(k in cand_lower for k in keywords)
                if q_has and c_has:
                    score += 0.10  # Bonus for matching material
                    break

            final_score = max(0.0, min(1.0, round(score, 4)))

            reranked.append({
                "id_pekerjaan": cand["id_pekerjaan"],
                "nama_pekerjaan": cand["nama_pekerjaan"],
                "satuan": cand["satuan"],
                "score": final_score,
                "base_score": base_sim,
            })

        # Sort by reranked final score descending
        reranked.sort(key=lambda x: x["score"], reverse=True)

        for rank, item in enumerate(reranked, 1):
            item["rank"] = rank

        return reranked

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
            if item_unit:
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
        if not self._ready:
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
                "ahsp_candidates": None,
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
        Get paginated list of all AHSP items. Optionally filter by search query.
        """
        if search_query.strip():
            results = self.search(search_query, top_k=min(limit * 2, 100))
            start = (page - 1) * limit
            end = start + limit
            paged = results[start:end]
            return {
                "items": paged,
                "total": len(results),
                "page": page,
                "limit": limit,
                "total_pages": max(1, (len(results) + limit - 1) // limit),
            }
        else:
            start = (page - 1) * limit
            end = start + limit
            paged = self._ahsp_items[start:end]
            return {
                "items": [item.to_dict() for item in paged],
                "total": self._total_items,
                "page": page,
                "limit": limit,
                "total_pages": max(1, (self._total_items + limit - 1) // limit),
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
