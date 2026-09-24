"""
rab_audit.py — FastAPI Router untuk AI RAB Auditor

Endpoint: POST /api/v2/ai/rab-audit

Menjalankan dual-layer validation:
- Layer 1: Deterministic Engine (volume zero, price deviation)
- Layer 2: LLM Contextual Reasoning (missing scope, volume sanity)

Hasilnya diagregasi menjadi Health Score (0-100) dan dikirim sebagai JSON response.

Sesuai PRD Section 9 "API Data Contract".
"""

import logging
import time

from fastapi import APIRouter, HTTPException
from starlette.concurrency import run_in_threadpool

from rab_auditor.schemas import RABAuditRequest, RABAuditResponse
from rab_auditor.engine import run_deterministic_audit
from rab_auditor.llm_engine import run_llm_audit
from rab_auditor.scoring import aggregate_audit_results

logger = logging.getLogger(__name__)

router = APIRouter(tags=["AI RAB Auditor"])


@router.post(
    "/api/v2/ai/rab-audit",
    response_model=RABAuditResponse,
    summary="Audit RAB dengan AI",
    description=(
        "Menjalankan validasi kelayakan RAB secara otomatis menggunakan "
        "Dual-Layer Architecture: Deterministic Engine + LLM Reasoning."
    ),
)
async def audit_rab(request: RABAuditRequest) -> RABAuditResponse:
    """
    Endpoint utama AI RAB Auditor.

    Menerima daftar item RAB + konteks proyek, lalu menjalankan:
    1. Layer 1: Validasi deterministic (volume nol, deviasi harga vs HSPK)
    2. Layer 2: LLM reasoning (missing scope, volume sanity)
    3. Agregasi skor kesehatan (0-100)
    """
    start_time = time.time()

    if not request.items:
        raise HTTPException(
            status_code=400,
            detail="Daftar item RAB kosong. Minimal 1 item diperlukan untuk audit."
        )

    province = request.project_context.location.province
    city = request.project_context.location.city_regency

    logger.info(
        f"Memulai audit RAB: {len(request.items)} items, "
        f"lokasi={city}/{province}, project={request.project_id}"
    )

    # ── Layer 1: Deterministic Engine (via threadpool) ──
    deterministic_anomalies = await run_in_threadpool(
        run_deterministic_audit,
        request.items,
        province,
        city,
    )

    # ── Layer 2: LLM Contextual Reasoning (via threadpool) ──
    try:
        missing_scopes, volume_anomalies = await run_in_threadpool(
            run_llm_audit,
            request.project_context,
            request.items,
        )
    except Exception as e:
        logger.warning(f"LLM audit gagal (fallback ke Layer 1 saja): {e}")
        missing_scopes = []
        volume_anomalies = []

    # ── Gabungkan anomali Layer 1 + Layer 2 ──
    all_anomalies = deterministic_anomalies + volume_anomalies

    # ── Agregasi & Scoring ──
    response = aggregate_audit_results(
        total_items=len(request.items),
        anomalies=all_anomalies,
        missing_scopes=missing_scopes,
    )

    elapsed = time.time() - start_time
    logger.info(f"Audit RAB selesai dalam {elapsed:.2f}s — Score: {response.data.health_score}/100")

    return response
