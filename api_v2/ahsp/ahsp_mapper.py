"""
AHSP Mapping Engine — Semantic Vector Search for Work Item → AHSP Code Mapping.

Uses ChromaDB (embedded vector database) + sentence-transformers to semantically
match AI-generated work item names to standardized AHSP (Analisis Harga Satuan Pekerjaan)
codes from the master Excel dataset (~2816 items).

Architecture:
  1. Startup: Load Excel → generate embeddings → index into ChromaDB
  2. Runtime: For each AI work item, query ChromaDB for top-N similar AHSP items
  3. Apply confidence thresholds → auto-map, suggest, or flag for manual mapping
"""

import os
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

# Confidence thresholds
THRESHOLD_HIGH = 0.85
THRESHOLD_MEDIUM = 0.65

# Number of candidates to return for medium confidence
TOP_K_CANDIDATES = 3
TOP_K_SEARCH_DEFAULT = 5


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
    Singleton-style AHSP Mapping Engine using ChromaDB + sentence-transformers.
    
    Usage:
        engine = AHSPMapperEngine()
        engine.initialize()  # Call once at startup
        
        # Search
        results = engine.search("cor beton kolom", top_k=5)
        
        # Map single item
        mapping = engine.map_single_item("Pengecoran Beton Kolom K1", "m3")
        
        # Bulk map entire takeoff response
        enriched = engine.map_takeoff_response(takeoff_response)
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
        """Compute MD5 hash of the Excel file for change detection."""
        try:
            h = hashlib.md5()
            with open(AHSP_EXCEL_PATH, "rb") as f:
                for chunk in iter(lambda: f.read(8192), b""):
                    h.update(chunk)
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
        Deletes existing collection if present, then creates a new one.
        """
        # Delete existing collection if it exists
        try:
            self._chroma_client.delete_collection(COLLECTION_NAME)
            logger.info(f"Deleted existing collection '{COLLECTION_NAME}'.")
        except Exception:
            pass  # Collection doesn't exist yet

        # Create new collection
        self._collection = self._chroma_client.create_collection(
            name=COLLECTION_NAME,
            embedding_function=self._embedding_fn,
            metadata={"hnsw:space": "cosine"},
        )

        # Prepare documents, IDs, and metadata
        documents = []
        ids = []
        metadatas = []

        for idx, item in enumerate(self._ahsp_items):
            # Combine nama + satuan for richer embedding context
            doc_text = item.nama_pekerjaan
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

        # ChromaDB has a batch limit (~41666 items), but our 2816 items are well within
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

    def search(self, query: str, top_k: int = TOP_K_SEARCH_DEFAULT) -> List[Dict[str, Any]]:
        """
        Perform semantic search for AHSP items matching the query.
        
        Returns list of dicts:
        [
            {
                "id_pekerjaan": "2.2.1.6.6",
                "nama_pekerjaan": "Pengecoran Beton ...",
                "satuan": "m3",
                "score": 0.89,
                "rank": 1
            },
            ...
        ]
        """
        if not self._ready or not self._collection:
            logger.warning("AHSP Mapper not ready. Returning empty results.")
            return []

        try:
            results = self._collection.query(
                query_texts=[query],
                n_results=min(top_k, self._total_items),
                include=["metadatas", "distances"],
            )

            output = []
            if results and results["metadatas"] and results["distances"]:
                for rank, (meta, distance) in enumerate(
                    zip(results["metadatas"][0], results["distances"][0]), 1
                ):
                    # ChromaDB cosine distance: 0 = identical, 2 = opposite
                    # Convert to similarity score: 1 - (distance / 2)
                    similarity = 1.0 - (distance / 2.0)

                    output.append({
                        "id_pekerjaan": meta["id_pekerjaan"],
                        "nama_pekerjaan": meta["nama_pekerjaan"],
                        "satuan": meta["satuan"],
                        "score": round(similarity, 4),
                        "rank": rank,
                    })

            return output

        except Exception as e:
            logger.error(f"AHSP search error: {e}", exc_info=True)
            return []

    def map_single_item(
        self, item_name: str, item_unit: str = ""
    ) -> Dict[str, Any]:
        """
        Map a single work item name to the best matching AHSP code.
        
        Returns:
        {
            "ahsp_code": "2.2.1.6.6" | None,
            "ahsp_name": "Pengecoran Beton ..." | None,
            "ahsp_unit": "m3" | None,
            "ahsp_score": 0.89 | None,
            "ahsp_status": "mapped_high" | "mapped_medium" | "unmapped",
            "ahsp_candidates": [...] | None  (for medium confidence)
        }
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

        # Build query with unit context for better matching
        query = item_name
        if item_unit:
            query += f" ({item_unit})"

        candidates = self.search(query, top_k=TOP_K_CANDIDATES + 2)

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
        
        Args:
            takeoff_response: DynamicTakeoffResponse instance
            
        Returns:
            The same response with AHSP fields populated on each item.
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
        
        Returns:
        {
            "items": [...],
            "total": 2816,
            "page": 1,
            "limit": 50,
            "total_pages": 57
        }
        """
        if search_query.strip():
            # Use vector search for filtered results
            results = self.search(search_query, top_k=min(limit * 2, 100))
            # Apply pagination to search results
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
            # Return raw items with pagination
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
        Useful when the Excel file has been updated.
        """
        try:
            start_time = time.time()
            logger.info("Force re-indexing AHSP Vector DB...")

            # Reload Excel
            self._ahsp_items = self._load_ahsp_from_excel()
            self._total_items = len(self._ahsp_items)

            if self._total_items == 0:
                return {"success": False, "error": "No items loaded from Excel."}

            # Rebuild index
            self._build_vector_index()

            # Update hash
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


# ─────────────────────────────────────────────────────────────────────
# Module-level singleton instance
# ─────────────────────────────────────────────────────────────────────
mapper_engine = AHSPMapperEngine()


def initialize_mapper():
    """
    Initialize the global mapper engine.
    Call this once at application startup (e.g., in main.py lifespan event).
    """
    global mapper_engine
    mapper_engine.initialize()
