# Pydantic Data Schemas — lihat docs/modules/schemas.md untuk dokumentasi lengkap.

from typing import List, Optional
from pydantic import BaseModel, Field, field_validator


class AHSPCandidateItem(BaseModel):
    """Satu kandidat item AHSP dari hasil vector semantic search."""

    id_pekerjaan: str = Field(default="", description="ID unik pekerjaan AHSP (contoh: '2.2.1.6.6')")
    nama_pekerjaan: str = Field(default="", description="Nama pekerjaan standar AHSP")
    satuan: str = Field(default="", description="Satuan standar AHSP (m2, m3, kg, unit, dll.)")
    score: float = Field(default=0.0, description="Similarity score dari vector semantic search (0.0 – 1.0)")


class CoverageAuditItem(BaseModel):
    """Item audit kelengkapan lembar gambar PDF per sheet."""

    sheet: str = Field(..., description="Kode atau judul lembar gambar (contoh: 'ARS-01', 'STR-03')")
    produced_items: List[str] = Field(
        default_factory=list,
        description="Daftar ID item yang dihasilkan, atau penjelasan jika lembar menghasilkan 0 item"
    )
    status: str = Field(default="ok", description="Status audit: 'ok' atau 'skipped'")


class BIMElementQuantity(BaseModel):
    """Kuantitas elemen 3D BIM per kombinasi level, kategori IFC, family, dan material."""

    level: str = Field(default="Unassigned Level", description="Nama level/lantai bangunan (contoh: 'Lantai 1', 'Lantai 2')")
    category: str = Field(..., description="Kategori IFC elemen (contoh: 'IfcWall', 'IfcColumn', 'IfcBeam')")
    family_name: str = Field(..., description="Nama tipe / family elemen BIM")
    material: str = Field(default="Standard Material", description="Bahan/material elemen BIM")
    count: int = Field(default=1, description="Jumlah elemen dalam grup agregasi")
    total_volume_m3: float = Field(default=0.0, description="Total volume bersih elemen (m³)")
    total_area_m2: float = Field(default=0.0, description="Total luas bersih elemen (m²)")
    total_length_m: float = Field(default=0.0, description="Total panjang elemen (m)")


class ProjectInfo(BaseModel):
    """Informasi dasar identitas proyek konstruksi (judul, klien, status)."""

    title: str = Field(default="Analisis CAD DWG", description="Nama/judul proyek atau gambar teknik")
    client: str = Field(default="Client", description="Nama klien atau pemilik proyek")
    status: str = Field(default="Perencanaan", description="Status proyek (contoh: 'Perencanaan', 'Konstruksi')")


class EstimateItem(BaseModel):
    """Satu item pekerjaan WBS dari hasil AI takeoff, beserta AHSP mapping."""

    id: str = Field(..., description="ID unik item WBS (contoh: 'item-A-1', 'item-B-3')")
    type: str = Field(default="item", description="Tipe baris: selalu 'item' untuk item pekerjaan")
    sectionCode: str = Field(..., description="Kode seksi induk (contoh: 'A', 'B', 'C')")
    no: int = Field(..., description="Nomor urut pekerjaan dalam seksi (1, 2, 3...)")
    code: str = Field(default="CUSTOM", description="Kode AHSP atau kode kustom CAD")
    name: str = Field(..., description="Uraian pekerjaan spesifik yang dihasilkan AI")
    volume: float = Field(
        ...,
        description="Volume/kuantitas hasil kalkulasi AI. HARUS POSITIF > 0.0. Dilarang keras mengembalikan 0.0!"
    )
    unit: str = Field(..., description="Satuan pekerjaan (m3, m2, m1, unit, titik, kg, ls, set, lbr, btg)")
    confidence: str = Field(default="high", description="Tingkat keyakinan AI: 'high' atau 'medium'")
    warning_note: Optional[str] = Field(
        default=None,
        description="Catatan dan rumus rincian kalkulasi volume dari AI (contoh: '10m x 5m = 50 m2')"
    )

    # --- Field AHSP Mapping (diisi oleh AHSPMapperEngine setelah takeoff AI) ---
    ahsp_code: Optional[str] = Field(default=None, description="Kode standar AHSP yang di-mapping (contoh: '2.2.1.6.6')")
    ahsp_name: Optional[str] = Field(default=None, description="Nama pekerjaan standar AHSP")
    ahsp_unit: Optional[str] = Field(default=None, description="Satuan standar dari AHSP")
    ahsp_score: Optional[float] = Field(default=None, description="Similarity score AHSP mapping (0.0 – 1.0)")
    ahsp_status: str = Field(
        default="unmapped",
        description="Status mapping AHSP: 'mapped_high', 'mapped_medium', atau 'unmapped'"
    )
    ahsp_candidates: Optional[List[AHSPCandidateItem]] = Field(
        default=None,
        description="Top-3 kandidat AHSP untuk status medium/low confidence (ditampilkan sebagai popover di frontend)"
    )

    @field_validator("volume", mode="before")
    def ensure_valid_volume(cls, v):
        """
        Validasi dan normalisasi nilai volume.

        Meneruskan nilai eksak dari AI tanpa modifikasi. Jika nilai tidak dapat
        dikonversi ke float, dikembalikan sebagai 0.0 sebagai fallback aman.
        Tidak pernah menginjeksi nilai default statis.
        """
        try:
            val = float(v)
            return round(val, 2)
        except (ValueError, TypeError):
            return 0.0


class EstimateSection(BaseModel):
    """Header seksi WBS (contoh: 'PEKERJAAN PERSIAPAN', 'PEKERJAAN PONDASI')."""

    id: str = Field(..., description="ID unik seksi (contoh: 'sec-A', 'sec-B')")
    type: str = Field(default="section", description="Tipe baris: selalu 'section' untuk header seksi")
    code: str = Field(..., description="Kode seksi unik (A, B, C, D, E, ...)")
    name: str = Field(..., description="Nama kategori WBS yang dihasilkan AI secara dinamis")


class WBSSectionBlock(BaseModel):
    """Blok WBS: satu section header + daftar item pekerjaan di bawahnya."""

    section: EstimateSection = Field(..., description="Objek header seksi WBS")
    items: List[EstimateItem] = Field(
        default_factory=list,
        description="Daftar item pekerjaan yang berada di bawah seksi ini"
    )


class DynamicTakeoffResponse(BaseModel):
    """Root response AI takeoff yang dikembalikan oleh seluruh endpoint /api/v2/takeoff/*."""

    project: ProjectInfo
    project_summary: str = Field(..., description="Ringkasan analisis kuantitas oleh AI (narasi singkat)")
    wbs_sections: List[WBSSectionBlock] = Field(
        default_factory=list,
        description="Daftar seksi WBS beserta item pekerjaan dari AI"
    )
    coverage_audit: Optional[List[CoverageAuditItem]] = Field(
        default=None,
        description="Audit kelengkapan lembar gambar PDF (hanya ada untuk analisis PDF)"
    )

    def to_frontend_format(self) -> dict:
        """
        Meratakan struktur WBS hierarkis menjadi array datar untuk frontend.

        Menggabungkan header seksi dan item pekerjaan ke dalam satu array tunggal
        yang dapat langsung diiterasi oleh komponen tabel di React (Anggaran.jsx).

        Returns:
            dict: Dictionary dengan key 'project', 'items' (flat array), 'coverage_audit', dan 'raw_llm_response'.
        """
        flat_rows = []
        for wbs in self.wbs_sections:
            flat_rows.append(wbs.section.model_dump())
            for item in wbs.items:
                flat_rows.append(item.model_dump())
        return {
            "project": self.project.model_dump(),
            "items": flat_rows,
            "coverage_audit": [c.model_dump() for c in self.coverage_audit] if self.coverage_audit else None,
            "raw_llm_response": self.model_dump()
        }
