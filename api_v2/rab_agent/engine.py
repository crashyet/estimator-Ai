"""
engine.py — Core AI Reasoning Engine untuk AI RAB Co-Pilot Agent

Menghubungkan:
1. Gemini API via google-genai SDK
2. Tool pencarian AHSP (ahsp_mapper) untuk grounding data
3. Validasi skema & kalkulasi matematika deterministik
"""

import os
import re
import json
import logging
from typing import List, Dict, Any, Optional

from dotenv import load_dotenv

from rab_agent.schemas import (
    RABAgentRequest, RABAgentResponse, RABAction, RABItemContext
)
from rab_agent.prompts import (
    RAB_AGENT_SYSTEM_PROMPT, build_agent_user_prompt
)
from rab_agent.tools import (
    search_ahsp_candidates, calculate_action_deltas
)

load_dotenv()
logger = logging.getLogger(__name__)

DEFAULT_GEMINI_KEY = os.getenv("GEMINI_API_KEY", "")
DEFAULT_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")

_gemini_client = None


def get_gemini_client():
    """Mengambil instance singleton client Gemini (google-genai SDK)."""
    global _gemini_client
    if _gemini_client is None:
        try:
            from google import genai
            api_key = os.getenv("GEMINI_API_KEY", DEFAULT_GEMINI_KEY)
            _gemini_client = genai.Client(api_key=api_key)
            logger.info("Gemini client berhasil diinisialisasi untuk RAB Co-Pilot Agent.")
        except Exception as e:
            logger.error(f"Gagal inisialisasi Gemini client: {e}")
            _gemini_client = None
    return _gemini_client


def _extract_search_keywords(prompt: str) -> List[str]:
    """Ekstraksi kata kunci material/pekerjaan dari prompt user untuk grounding AHSP."""
    keywords = []
    # Pola kata setelah "ganti ... jadi/ke [target]" atau "tambah [target]"
    match_swap = re.search(r'(?:jadi|ke|dengan|pake|pakai)\s+([a-zA-Z0-9\s]+?)(?:$|\bdi\b|\buntuk\b|\bsemua\b)', prompt, re.IGNORECASE)
    if match_swap:
        cand = match_swap.group(1).strip()
        if len(cand) > 3:
            keywords.append(cand)

    match_add = re.search(r'(?:tambah|tambahkan|pasang)\s+(?:pekerjaan\s+)?([a-zA-Z0-9\s]+?)(?:$|\bdi\b|\bsebanyak\b)', prompt, re.IGNORECASE)
    if match_add:
        cand = match_add.group(1).strip()
        if len(cand) > 3 and cand not in keywords:
            keywords.append(cand)

    return keywords


def _build_ahsp_hint(prompt: str) -> Optional[str]:
    """Mencari referensi AHSP dari database jika user menyebut material spesifik."""
    keywords = _extract_search_keywords(prompt)
    if not keywords:
        return None

    hints = []
    for kw in keywords[:2]:
        candidates = search_ahsp_candidates(kw, top_k=3)
        if candidates:
            hints.append(f"Kandidat untuk '{kw}':")
            for c in candidates:
                hints.append(f"  - [{c['id_pekerjaan']}] {c['nama_pekerjaan']} (Satuan: {c['satuan']})")

    return "\n".join(hints) if hints else None


async def run_rab_agent(request: RABAgentRequest) -> RABAgentResponse:
    """
    Eksekusi utama AI RAB Co-Pilot Agent:
    1. Pre-fetch AHSP hints jika ada kebutuhan substitusi material/pekerjaan
    2. Susun prompt komprehensif (tabel aktif + konteks + instruksi)
    3. Generate reasoning & structured actions via Gemini
    4. Hitung matematika biaya (cost delta) secara deterministik
    """
    items_map: Dict[int, RABItemContext] = {it.id: it for it in request.items}

    # 1. Grounding AHSP jika ada indikasi spesifikasi baru
    ahsp_hint = _build_ahsp_hint(request.prompt)

    # 2. Susun prompt
    user_prompt = build_agent_user_prompt(
        prompt=request.prompt,
        items=request.items,
        project_context=request.project_context,
        history=request.history,
        ahsp_hint=ahsp_hint
    )

    client = get_gemini_client()
    if not client:
        return RABAgentResponse(
            reply_message="Maaf, koneksi ke AI service (Gemini) belum tersedia. Silakan pastikan GEMINI_API_KEY telah dikonfigurasi.",
            actions=[],
            cost_impact=0.0,
            affected_items_count=0
        )

    try:
        from google.genai import types

        config = types.GenerateContentConfig(
            system_instruction=RAB_AGENT_SYSTEM_PROMPT,
            temperature=0.2,
            response_mime_type="application/json"
        )

        response = client.models.generate_content(
            model=DEFAULT_MODEL,
            contents=user_prompt,
            config=config
        )

        raw_text = response.text.strip()
        data = json.loads(raw_text)

        reply_message = data.get("reply_message", "Saya telah menyiapkan usulan perubahan berikut:")
        raw_actions = data.get("actions", [])

        # 3. Validasi & normalisasi actions
        validated_actions: List[Dict[str, Any]] = []
        for act in raw_actions:
            act_type = str(act.get("action_type", "")).upper()
            if act_type not in ("UPDATE_ITEM", "ADD_ITEM", "DELETE_ITEM"):
                continue

            target_id = act.get("target_item_id")
            if target_id is not None:
                try:
                    target_id = int(target_id)
                except (ValueError, TypeError):
                    target_id = None

            # Fallback jika target_id hilang tapi ada kata kunci di deskripsi item
            if act_type in ("UPDATE_ITEM", "DELETE_ITEM") and target_id is None:
                act_desc = (act.get("description") or "").lower()
                for it_id, it in items_map.items():
                    if it.description.lower() in act_desc or any(w in act_desc for w in it.description.lower().split()):
                        target_id = it_id
                        break

            validated_actions.append({
                "action_type": act_type,
                "target_item_id": target_id,
                "target_section_id": act.get("target_section_id"),
                "target_category": act.get("target_category") or "",
                "description": act.get("description") or f"{act_type} pada tabel RAB",
                "changes": act.get("changes") or {},
                "old_values": None,
                "cost_delta": 0.0
            })

        # 4. Hitung matematika deterministik untuk cost impact
        cost_impact = calculate_action_deltas(validated_actions, items_map)

        # 5. Bangun typed RABAction objects
        action_objs = [RABAction(**a) for a in validated_actions]

        return RABAgentResponse(
            reply_message=reply_message,
            actions=action_objs,
            cost_impact=cost_impact,
            affected_items_count=len(action_objs)
        )

    except Exception as e:
        logger.error(f"Error saat mengeksekusi RAB Agent: {e}", exc_info=True)
        return RABAgentResponse(
            reply_message=f"Terjadi kesalahan saat memproses permintaan: {str(e)}",
            actions=[],
            cost_impact=0.0,
            affected_items_count=0
        )
