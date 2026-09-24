"""
schemas.py — Pydantic Data Models untuk AI RAB Co-Pilot Agent

Mendefinisikan kontrak data interaksi agen:
- RABItemContext: snapshot baris tabel yang ada di frontend
- RABAction: usulan mutasi (ADD, UPDATE, DELETE)
- RABAgentRequest / RABAgentResponse: payload komunikasi
"""

from typing import List, Optional, Dict, Any, Literal
from pydantic import BaseModel, Field
from datetime import datetime, timezone


class ProjectLocation(BaseModel):
    province: str = Field(default="Jawa Tengah", description="Provinsi proyek")
    city_regency: str = Field(default="Banyumas", description="Kabupaten/Kota proyek")


class ProjectContext(BaseModel):
    project_name: str = Field(default="Proyek Konstruksi", description="Nama proyek")
    building_type: str = Field(default="Rumah Tinggal", description="Tipe bangunan")
    location: Optional[ProjectLocation] = Field(default=None, description="Lokasi proyek")
    building_area_m2: float = Field(default=0.0, description="Luas bangunan")
    number_of_floors: int = Field(default=1, description="Jumlah lantai")


class RABItemContext(BaseModel):
    """Satu baris item pekerjaan pada tabel RAB."""
    id: int = Field(..., description="ID item pekerjaan")
    section_id: Optional[int] = Field(default=None, description="ID section / WBS category")
    category: str = Field(default="", description="Nama kategori / WBS")
    description: str = Field(..., description="Uraian pekerjaan")
    ahsp_code: Optional[str] = Field(default="", description="Kode AHSP")
    volume: float = Field(default=0.0, description="Volume")
    unit: str = Field(default="", description="Satuan (m3, m2, bh, titik, dll)")
    unit_price: float = Field(default=0.0, description="Harga satuan (Rp)")
    total_price: float = Field(default=0.0, description="Total harga (Rp)")


class ChatMessage(BaseModel):
    role: Literal["user", "assistant"] = Field(..., description="Peran pesan")
    content: str = Field(..., description="Isi pesan")


class RABAction(BaseModel):
    """Aksi spesifik yang diusulkan oleh Agent terhadap tabel RAB."""
    action_type: Literal["UPDATE_ITEM", "ADD_ITEM", "DELETE_ITEM"] = Field(
        ..., description="Tipe mutasi: UPDATE_ITEM, ADD_ITEM, atau DELETE_ITEM"
    )
    target_item_id: Optional[int] = Field(
        default=None, description="ID item yang diubah atau dihapus (khusus UPDATE & DELETE)"
    )
    target_section_id: Optional[int] = Field(
        default=None, description="ID section untuk item baru (khusus ADD_ITEM)"
    )
    target_category: Optional[str] = Field(
        default=None, description="Nama kategori target"
    )
    description: str = Field(
        ..., description="Penjelasan manusia tentang aksi ini (e.g. 'Ganti keramik 40x40 menjadi granit 60x60')"
    )
    changes: Dict[str, Any] = Field(
        default_factory=dict,
        description="Field yang berubah atau ditambahkan: item_name, volume, unit, unit_price, ahsp_code, ahsp_name"
    )
    old_values: Optional[Dict[str, Any]] = Field(
        default=None, description="Nilai sebelum diubah (untuk preview diff)"
    )
    cost_delta: float = Field(
        default=0.0, description="Selisih nominal (Rp). Positif = tambah biaya, negatif = penghematan"
    )


class RABAgentRequest(BaseModel):
    """Request dari frontend ke AI RAB Agent."""
    project_id: str = Field(..., description="UUID proyek")
    prompt: str = Field(..., description="Instruksi perintah pengguna dalam bahasa alami")
    items: List[RABItemContext] = Field(default_factory=list, description="Daftar item RAB saat ini")
    project_context: Optional[ProjectContext] = Field(default=None, description="Konteks proyek")
    history: Optional[List[ChatMessage]] = Field(default=None, description="Riwayat percakapan sebelumnya")


class RABAgentResponse(BaseModel):
    """Response dari AI RAB Agent ke frontend."""
    reply_message: str = Field(..., description="Pesan balasan ramah & reasoning dari agent")
    actions: List[RABAction] = Field(default_factory=list, description="Daftar aksi yang diusulkan")
    cost_impact: float = Field(default=0.0, description="Total estimasi perubahan biaya (Rp)")
    affected_items_count: int = Field(default=0, description="Jumlah item yang terdampak")
    timestamp: str = Field(
        default_factory=lambda: datetime.now(timezone.utc).isoformat(),
        description="Waktu eksekusi"
    )
