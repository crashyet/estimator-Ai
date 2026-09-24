"""
llm_engine.py — Layer 2: LLM Contextual Reasoning Engine

Menggunakan Gemini API (google-genai SDK) untuk:
1. Missing Scope Detection — mendeteksi item pekerjaan wajib yang hilang
2. Volume Sanity Check — memeriksa kewajaran volume terhadap luas bangunan

Fitur Graceful Fallback:
- Jika LLM timeout/gagal → return empty list (Layer 1 tetap jalan)
- Menggunakan Structured Output (JSON Schema) dari Gemini

Sesuai PRD Section 6 "Layer 2: LLM Contextual Reasoning" dan
Section 10.3 "Resiliensi & Graceful Fallback".
"""

import os
import json
import logging
from typing import List, Optional

from dotenv import load_dotenv

from rab_auditor.schemas import (
    RABAuditItem, AnomalyResult, MissingScopeResult, ProjectContext,
)
from rab_auditor.prompts import (
    MISSING_SCOPE_SYSTEM_PROMPT, build_missing_scope_user_prompt,
    VOLUME_SANITY_SYSTEM_PROMPT, build_volume_sanity_user_prompt,
)

load_dotenv()
logger = logging.getLogger(__name__)

DEFAULT_GEMINI_KEY = os.getenv("GEMINI_API_KEY", "")
DEFAULT_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")


# ─────────────────────────────────────────────────────────────────────
# GEMINI CLIENT SINGLETON
# ─────────────────────────────────────────────────────────────────────

_gemini_client = None


def _get_gemini_client():
    """Lazy-init Gemini client singleton."""
    global _gemini_client
    if _gemini_client is None:
        try:
            from google import genai
            _gemini_client = genai.Client(api_key=DEFAULT_GEMINI_KEY)
            logger.info("Gemini client initialized for RAB Auditor.")
        except Exception as e:
            logger.error(f"Gagal inisialisasi Gemini client: {e}")
            _gemini_client = None
    return _gemini_client


# ─────────────────────────────────────────────────────────────────────
# HELPER: Format Items untuk Prompt
# ─────────────────────────────────────────────────────────────────────

def _format_items_summary(items: List[RABAuditItem]) -> str:
    """Format ringkasan item RAB untuk prompt LLM (kategori + nama + satuan)."""
    lines = []
    current_category = ""
    for item in items:
        if item.category and item.category != current_category:
            current_category = item.category
            lines.append(f"\n### Kategori: {current_category}")
        lines.append(f"- [{item.id}] {item.description} ({item.volume} {item.unit})")
    return "\n".join(lines)


def _format_items_with_volume(items: List[RABAuditItem]) -> str:
    """Format item dengan volume untuk volume sanity prompt."""
    lines = []
    for item in items:
        if item.volume > 0:
            lines.append(f"{item.id} | {item.description} | {item.volume} | {item.unit}")
    return "\n".join(lines) if lines else "(Tidak ada item dengan volume > 0)"


# ─────────────────────────────────────────────────────────────────────
# MISSING SCOPE DETECTION
# ─────────────────────────────────────────────────────────────────────

def detect_missing_scope(
    project_context: ProjectContext,
    items: List[RABAuditItem],
) -> List[MissingScopeResult]:
    """
    Deteksi item pekerjaan wajib yang hilang menggunakan Gemini LLM.

    Args:
        project_context: Konteks proyek (tipe, lokasi, luas)
        items: Daftar item RAB

    Returns:
        List MissingScopeResult. Return empty list jika LLM gagal (graceful fallback).
    """
    if not items:
        return []

    client = _get_gemini_client()
    if client is None:
        logger.warning("Gemini client not available. Skipping missing scope detection.")
        return []

    try:
        from google.genai import types

        items_summary = _format_items_summary(items)
        user_prompt = build_missing_scope_user_prompt(
            project_context.model_dump(), items_summary
        )

        # JSON Schema untuk structured output
        missing_scope_schema = types.Schema(
            type="ARRAY",
            items=types.Schema(
                type="OBJECT",
                properties={
                    "category": types.Schema(type="STRING", description="Kategori pekerjaan terkait"),
                    "missing_item": types.Schema(type="STRING", description="Nama item yang hilang"),
                    "confidence": types.Schema(type="NUMBER", description="Keyakinan 0.0-1.0"),
                    "reason": types.Schema(type="STRING", description="Alasan item ini wajib ada"),
                },
                required=["category", "missing_item", "confidence", "reason"],
            ),
        )

        response = client.models.generate_content(
            model=DEFAULT_MODEL,
            contents=user_prompt,
            config=types.GenerateContentConfig(
                system_instruction=MISSING_SCOPE_SYSTEM_PROMPT,
                response_mime_type="application/json",
                response_schema=missing_scope_schema,
                temperature=0.2,
                max_output_tokens=4096,
            ),
        )

        raw_text = response.text.strip()
        parsed = json.loads(raw_text)

        results = []
        for item in parsed:
            results.append(MissingScopeResult(
                category=item.get("category", ""),
                missing_item=item.get("missing_item", ""),
                confidence=float(item.get("confidence", 0.0)),
                reason=item.get("reason", ""),
            ))

        logger.info(f"Missing scope detection selesai: {len(results)} item terdeteksi.")
        return results

    except Exception as e:
        logger.error(f"LLM Missing Scope detection gagal (graceful fallback): {e}")
        return []


# ─────────────────────────────────────────────────────────────────────
# VOLUME SANITY CHECK
# ─────────────────────────────────────────────────────────────────────

def check_volume_sanity(
    project_context: ProjectContext,
    items: List[RABAuditItem],
) -> List[AnomalyResult]:
    """
    Periksa kewajaran volume item terhadap luas bangunan menggunakan Gemini LLM.

    Args:
        project_context: Konteks proyek
        items: Daftar item RAB

    Returns:
        List AnomalyResult untuk volume outlier. Return empty list jika LLM gagal.
    """
    if not items or project_context.building_area_m2 <= 0:
        return []

    client = _get_gemini_client()
    if client is None:
        logger.warning("Gemini client not available. Skipping volume sanity check.")
        return []

    try:
        from google.genai import types

        items_text = _format_items_with_volume(items)
        user_prompt = build_volume_sanity_user_prompt(
            project_context.model_dump(), items_text
        )

        volume_anomaly_schema = types.Schema(
            type="ARRAY",
            items=types.Schema(
                type="OBJECT",
                properties={
                    "item_id": types.Schema(type="INTEGER", description="ID item bermasalah"),
                    "message": types.Schema(type="STRING", description="Penjelasan masalah volume"),
                    "recommendation": types.Schema(type="STRING", description="Rekomendasi perbaikan"),
                },
                required=["item_id", "message", "recommendation"],
            ),
        )

        response = client.models.generate_content(
            model=DEFAULT_MODEL,
            contents=user_prompt,
            config=types.GenerateContentConfig(
                system_instruction=VOLUME_SANITY_SYSTEM_PROMPT,
                response_mime_type="application/json",
                response_schema=volume_anomaly_schema,
                temperature=0.2,
                max_output_tokens=4096,
            ),
        )

        raw_text = response.text.strip()
        parsed = json.loads(raw_text)

        results = []
        for anomaly in parsed:
            results.append(AnomalyResult(
                item_id=int(anomaly.get("item_id", 0)),
                type="VOLUME_OUTLIER",
                severity="WARNING",
                field="volume",
                message=anomaly.get("message", ""),
                recommendation=anomaly.get("recommendation", ""),
            ))

        logger.info(f"Volume sanity check selesai: {len(results)} outlier terdeteksi.")
        return results

    except Exception as e:
        logger.error(f"LLM Volume Sanity check gagal (graceful fallback): {e}")
        return []


# ─────────────────────────────────────────────────────────────────────
# COMBINED LLM AUDIT
# ─────────────────────────────────────────────────────────────────────

def run_llm_audit(
    project_context: ProjectContext,
    items: List[RABAuditItem],
) -> tuple:
    """
    Jalankan seluruh Layer 2 (LLM) audit.

    Returns:
        (missing_scopes: List[MissingScopeResult], volume_anomalies: List[AnomalyResult])
    """
    missing_scopes = detect_missing_scope(project_context, items)
    volume_anomalies = check_volume_sanity(project_context, items)
    return missing_scopes, volume_anomalies
