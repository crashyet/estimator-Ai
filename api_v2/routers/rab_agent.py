"""
rab_agent.py — FastAPI Router untuk AI RAB Co-Pilot Agent

Endpoint: POST /api/v2/ai/rab-agent

Menangani permintaan interaktif untuk memodifikasi tabel RAB:
- Menerima prompt pengguna + snapshot baris tabel
- Memproses penalaran konteks (contextual reasoning) via Gemini + grounding AHSP
- Menghasilkan daftar aksi terstruktur (UPDATE, ADD, DELETE) beserta dampak biaya deterministik
"""

import time
import logging
from fastapi import APIRouter, HTTPException

from rab_agent.schemas import RABAgentRequest, RABAgentResponse
from rab_agent.engine import run_rab_agent

logger = logging.getLogger(__name__)

router = APIRouter(tags=["AI RAB Co-Pilot Agent"])


@router.post(
    "/api/v2/ai/rab-agent",
    response_model=RABAgentResponse,
    summary="Interaksi dengan AI RAB Co-Pilot Agent",
    description=(
        "Menerima instruksi pengguna dalam bahasa alami untuk memodifikasi, "
        "menambah, menghapus, atau mengoptimasi tabel RAB dengan output usulan aksi terstruktur."
    ),
)
async def handle_rab_agent(request: RABAgentRequest) -> RABAgentResponse:
    start_time = time.time()

    if not request.prompt or not request.prompt.strip():
        raise HTTPException(
            status_code=400,
            detail="Instruksi prompt tidak boleh kosong."
        )

    logger.info(
        f"[RAB-AGENT] Memproses prompt: '{request.prompt[:60]}...' "
        f"untuk proyek {request.project_id} dengan {len(request.items)} item aktif."
    )

    try:
        response = await run_rab_agent(request)
        elapsed = round((time.time() - start_time) * 1000, 2)
        logger.info(
            f"[RAB-AGENT] Sukses menghasilkan {len(response.actions)} usulan aksi "
            f"dalam {elapsed}ms. Cost impact: Rp {response.cost_impact:,.0f}"
        )
        return response
    except Exception as e:
        logger.error(f"[RAB-AGENT] Error: {e}", exc_info=True)
        raise HTTPException(
            status_code=500,
            detail=f"Terjadi kesalahan internal pada AI RAB Agent: {str(e)}"
        )
