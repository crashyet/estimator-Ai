"""
schemas.py — Pydantic Data Models untuk RAB Auditor

Mendefinisikan kontrak data request/response sesuai PRD Section 9 (API Data Contract).
Digunakan oleh FastAPI endpoint POST /api/v2/ai/rab-audit.
"""

from typing import List, Optional
from pydantic import BaseModel, Field
from datetime import datetime, timezone


# ─────────────────────────────────────────────────────────────────────
# REQUEST MODELS
# ─────────────────────────────────────────────────────────────────────

class ProjectLocation(BaseModel):
    """Lokasi proyek untuk menentukan referensi HSPK daerah."""
    province: str = Field(..., description="Nama provinsi (contoh: 'Jawa Tengah')")
    city_regency: str = Field(..., description="Nama kabupaten/kota (contoh: 'Banyumas')")


class ProjectContext(BaseModel):
    """Konteks proyek yang dibutuhkan untuk audit."""
    project_name: str = Field(default="Proyek Konstruksi", description="Nama proyek")
    building_type: str = Field(default="Rumah Tinggal", description="Tipe bangunan")
    location: ProjectLocation
    building_area_m2: float = Field(default=0.0, description="Luas bangunan (m²)")
    number_of_floors: int = Field(default=1, description="Jumlah lantai")
    currency: str = Field(default="IDR", description="Mata uang")


class RABAuditItem(BaseModel):
    """Satu item pekerjaan RAB yang akan diaudit."""
    id: int = Field(..., description="ID unik item")
    category: str = Field(default="", description="Kategori / jenis pekerjaan")
    description: str = Field(..., description="Uraian pekerjaan")
    ahsp_code: str = Field(default="", description="Kode AHSP jika sudah di-mapping")
    volume: float = Field(default=0.0, description="Volume pekerjaan")
    unit: str = Field(default="", description="Satuan pekerjaan (m3, m2, kg, dll)")
    unit_price: float = Field(default=0.0, description="Harga satuan (Rp)")
    total_price: float = Field(default=0.0, description="Total harga (Rp)")


class RABAuditRequest(BaseModel):
    """Payload request utama untuk audit RAB."""
    project_id: str = Field(..., description="UUID proyek")
    project_context: ProjectContext
    items: List[RABAuditItem] = Field(default_factory=list, description="Daftar item RAB")


# ─────────────────────────────────────────────────────────────────────
# RESPONSE MODELS
# ─────────────────────────────────────────────────────────────────────

class AnomalyResult(BaseModel):
    """Satu temuan anomali dari proses audit."""
    item_id: int = Field(..., description="ID item yang bermasalah")
    type: str = Field(
        ...,
        description="Tipe anomali: VOLUME_ZERO, PRICE_OVERPRICED, PRICE_UNDERPRICED, VOLUME_OUTLIER"
    )
    severity: str = Field(..., description="Tingkat keparahan: CRITICAL atau WARNING")
    field: str = Field(..., description="Kolom yang bermasalah: volume atau unit_price")
    message: str = Field(..., description="Pesan penjelasan anomali")
    recommendation: str = Field(default="", description="Rekomendasi perbaikan")
    current_value: Optional[float] = Field(default=None, description="Nilai saat ini dari user")
    benchmark_value: Optional[float] = Field(default=None, description="Nilai acuan HSPK")
    deviation_percent: Optional[float] = Field(default=None, description="Persentase deviasi")


class MissingScopeResult(BaseModel):
    """Satu temuan item pekerjaan yang hilang (Missing Scope)."""
    category: str = Field(..., description="Kategori pekerjaan terkait")
    missing_item: str = Field(..., description="Nama item yang seharusnya ada")
    confidence: float = Field(default=0.0, description="Tingkat keyakinan LLM (0.0 - 1.0)")
    reason: str = Field(default="", description="Alasan mengapa item ini wajib ada")


class AuditSummary(BaseModel):
    """Ringkasan cepat hasil audit."""
    total_items_checked: int = Field(default=0, description="Total item yang diperiksa")
    critical_count: int = Field(default=0, description="Jumlah temuan CRITICAL")
    warning_count: int = Field(default=0, description="Jumlah temuan WARNING")
    missing_scope_count: int = Field(default=0, description="Jumlah item hilang")


class AuditData(BaseModel):
    """Data hasil audit lengkap."""
    audit_timestamp: str = Field(
        default_factory=lambda: datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        description="Waktu audit (UTC ISO 8601)"
    )
    health_score: int = Field(default=100, description="Skor kesehatan RAB (0-100)")
    health_status: str = Field(default="EXCELLENT", description="Status: EXCELLENT/GOOD/NEEDS_REVIEW/POOR")
    summary: AuditSummary = Field(default_factory=AuditSummary)
    anomalies: List[AnomalyResult] = Field(default_factory=list)
    missing_scopes: List[MissingScopeResult] = Field(default_factory=list)


class RABAuditResponse(BaseModel):
    """Response payload utama dari endpoint audit RAB."""
    status: str = Field(default="success")
    data: AuditData = Field(default_factory=AuditData)
