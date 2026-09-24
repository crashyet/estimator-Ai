"""
scoring.py — RAB Health Score Calculator & Aggregator

Menghitung skor kesehatan RAB (0-100) berdasarkan temuan anomali dan missing scope.

Aturan Pengurangan Skor (PRD Section 7 FR-2):
- Baseline awal: 100 poin
- Setiap temuan CRITICAL: kurangi 15 poin
- Setiap temuan WARNING: kurangi 5 poin
- Setiap temuan MISSING_SCOPE: kurangi 10 poin
- Nilai minimum skor: 0

Status Mapping:
- ≥ 90: EXCELLENT (hijau)
- ≥ 75: GOOD (hijau muda)
- ≥ 50: NEEDS_REVIEW (kuning)
- < 50: POOR (merah)
"""

import logging
from typing import List

from rab_auditor.schemas import (
    AnomalyResult, MissingScopeResult, AuditSummary,
    AuditData, RABAuditResponse,
)

logger = logging.getLogger(__name__)

# Poin pengurangan per tipe temuan
PENALTY_CRITICAL = 15
PENALTY_WARNING = 5
PENALTY_MISSING_SCOPE = 10

# Batas skor
SCORE_BASELINE = 100
SCORE_MIN = 0

# Status thresholds
STATUS_EXCELLENT = 90
STATUS_GOOD = 75
STATUS_NEEDS_REVIEW = 50


def calculate_health_score(
    anomalies: List[AnomalyResult],
    missing_scopes: List[MissingScopeResult],
) -> int:
    """
    Hitung Health Score RAB berdasarkan temuan audit.

    Args:
        anomalies: List anomali dari Layer 1 + Layer 2
        missing_scopes: List missing scope dari Layer 2

    Returns:
        Skor 0-100
    """
    score = SCORE_BASELINE

    for anomaly in anomalies:
        if anomaly.severity == "CRITICAL":
            score -= PENALTY_CRITICAL
        elif anomaly.severity == "WARNING":
            score -= PENALTY_WARNING

    for _ in missing_scopes:
        score -= PENALTY_MISSING_SCOPE

    return max(score, SCORE_MIN)


def determine_health_status(score: int) -> str:
    """Map skor ke status string."""
    if score >= STATUS_EXCELLENT:
        return "EXCELLENT"
    elif score >= STATUS_GOOD:
        return "GOOD"
    elif score >= STATUS_NEEDS_REVIEW:
        return "NEEDS_REVIEW"
    else:
        return "POOR"


def build_audit_summary(
    total_items: int,
    anomalies: List[AnomalyResult],
    missing_scopes: List[MissingScopeResult],
) -> AuditSummary:
    """Bangun ringkasan cepat hasil audit."""
    critical_count = sum(1 for a in anomalies if a.severity == "CRITICAL")
    warning_count = sum(1 for a in anomalies if a.severity == "WARNING")

    return AuditSummary(
        total_items_checked=total_items,
        critical_count=critical_count,
        warning_count=warning_count,
        missing_scope_count=len(missing_scopes),
    )


def aggregate_audit_results(
    total_items: int,
    anomalies: List[AnomalyResult],
    missing_scopes: List[MissingScopeResult],
) -> RABAuditResponse:
    """
    Aggregasi seluruh hasil audit menjadi response final.

    Menggabungkan anomali Layer 1 + Layer 2 dan missing scope,
    lalu menghitung Health Score dan status.

    Args:
        total_items: Total item yang diperiksa
        anomalies: Gabungan anomali dari Layer 1 + Layer 2
        missing_scopes: Missing scope dari Layer 2

    Returns:
        RABAuditResponse siap dikirim ke frontend
    """
    score = calculate_health_score(anomalies, missing_scopes)
    status = determine_health_status(score)
    summary = build_audit_summary(total_items, anomalies, missing_scopes)

    logger.info(
        f"Audit Score: {score}/100 ({status}) — "
        f"Critical: {summary.critical_count}, Warning: {summary.warning_count}, "
        f"Missing: {summary.missing_scope_count}"
    )

    audit_data = AuditData(
        health_score=score,
        health_status=status,
        summary=summary,
        anomalies=anomalies,
        missing_scopes=missing_scopes,
    )

    return RABAuditResponse(status="success", data=audit_data)
