from typing import List, Optional
from pydantic import BaseModel, Field, field_validator

class AHSPCandidateItem(BaseModel):
    id_pekerjaan: str = Field(default="", description="ID pekerjaan AHSP")
    nama_pekerjaan: str = Field(default="", description="Nama pekerjaan AHSP")
    satuan: str = Field(default="", description="Satuan AHSP")
    score: float = Field(default=0.0, description="Similarity score")

class CoverageAuditItem(BaseModel):
    sheet: str = Field(..., description="Kode atau judul lembar gambar (misal: ARS-01, STR-02)")
    produced_items: List[str] = Field(default_factory=list, description="Daftar item ID yang dihasilkan atau alasan jika 0 item")
    status: str = Field(default="ok", description="Status audit")

class ProjectInfo(BaseModel):
    title: str = Field(default="Analisis CAD DWG", description="Nama/Judul Proyek atau Gambar Teknik")
    client: str = Field(default="Client", description="Nama Klien")
    status: str = Field(default="Perencanaan", description="Status perencanaan proyek")

class EstimateItem(BaseModel):
    id: str = Field(..., description="ID unik item, contoh 'item-A-1', 'item-B-1'")
    type: str = Field(default="item", description="Tipe row: selalu 'item'")
    sectionCode: str = Field(..., description="Kode seksi induk, contoh 'A', 'B', 'C'")
    no: int = Field(..., description="Nomor urut pekerjaan dalam seksi (1, 2, 3...)")
    code: str = Field(default="CUSTOM", description="Kode AHSP atau CAD")
    name: str = Field(..., description="Uraian pekerjaan spesifik dari CAD")
    volume: float = Field(..., description="Volume/Kuantitas hasil kalkulasi AI (ANGKA POSITIF > 0.0, misal: 14.4, 35.5, 8.0, 120.0). DILARANG KERAS MENGEMBALIKAN 0 ATAU 0.0!")
    unit: str = Field(..., description="Satuan (m3, m2, m1, unit, titik, kg, ls, set, lbr, btg)")
    confidence: str = Field(default="high", description="Tingkat keyakinan AI: 'high' atau 'medium'")
    warning_note: Optional[str] = Field(default=None, description="Catatan/rumus rincian kalkulasi volume dari AI")

    # --- AHSP Mapping Fields (populated by AHSPMapperEngine post-processing) ---
    ahsp_code: Optional[str] = Field(default=None, description="Kode AHSP standar yang di-mapping (e.g. '2.2.1.6.6')")
    ahsp_name: Optional[str] = Field(default=None, description="Nama pekerjaan standar AHSP")
    ahsp_unit: Optional[str] = Field(default=None, description="Satuan standar dari AHSP")
    ahsp_score: Optional[float] = Field(default=None, description="Similarity score mapping (0.0-1.0)")
    ahsp_status: str = Field(default="unmapped", description="Status mapping: 'mapped_high', 'mapped_medium', 'unmapped'")
    ahsp_candidates: Optional[List[AHSPCandidateItem]] = Field(default=None, description="Top-3 AHSP candidates untuk medium/low confidence")

    @field_validator("volume", mode="before")
    def ensure_valid_volume(cls, v):
        """Pass through AI's exact volume value. Use 0.0 for unparseable values — never inject static defaults."""
        try:
            val = float(v)
            return round(val, 2)
        except (ValueError, TypeError):
            return 0.0

class EstimateSection(BaseModel):
    id: str = Field(..., description="ID unik seksi, contoh 'sec-A', 'sec-B'")
    type: str = Field(default="section", description="Tipe row: selalu 'section'")
    code: str = Field(..., description="Kode seksi unik (A, B, C, D, E...)")
    name: str = Field(..., description="Nama Kategori WBS murni dari AI (misal 'PEKERJAAN PERSIAPAN & K3', 'PEKERJAAN TANAH', 'PEKERJAAN PONDASI', 'PEKERJAAN STRUKTUR BETON', 'PEKERJAAN DINDING', 'PEKERJAAN ATAP', 'PEKERJAAN PLAFON', 'PEKERJAAN INSTALASI MEP', dll)")

class WBSSectionBlock(BaseModel):
    section: EstimateSection = Field(..., description="Objek header seksi WBS")
    items: List[EstimateItem] = Field(default_factory=list, description="Daftar item pekerjaan di bawah seksi ini")

class DynamicTakeoffResponse(BaseModel):
    project: ProjectInfo
    project_summary: str = Field(..., description="Ringkasan analisis kuantitas CAD oleh AI")
    wbs_sections: List[WBSSectionBlock] = Field(default_factory=list, description="Daftar seksi WBS murni beserta item pekerjaan dari AI")
    coverage_audit: Optional[List[CoverageAuditItem]] = Field(default=None, description="Audit kelengkapan lembar gambar PDF vs item pekerjaan yang dihasilkan")

    def to_frontend_format(self) -> dict:
        """Flatten WBS sections and items into single flat array for CI4 / Frontend."""
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
