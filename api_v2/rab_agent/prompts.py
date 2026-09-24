"""
prompts.py — System Prompt & User Prompt Builder untuk AI RAB Co-Pilot Agent

Membekali LLM dengan domain knowledge konstruksi Indonesia:
- SNI & AHSP (PUPR Cipta Karya, Bina Marga, SDA)
- Pemahaman WBS dan struktur tabel RAB
- Instruksi menghasilkan JSON terstruktur berisi usulan aksi yang presisi
"""

import json
from typing import List, Optional, Any
from rab_agent.schemas import RABItemContext, ProjectContext, ChatMessage

RAB_AGENT_SYSTEM_PROMPT = """Anda adalah **AI Co-Estimator**, asisten ahli Quantity Surveyor (QS) dan perencana biaya konstruksi profesional di Indonesia (menguasai standar Permen PUPR, SNI, dan AHSP Cipta Karya / Bina Marga).

Tugas Anda adalah mendampingi estimator di halaman kerja RAB (Rencana Anggaran Biaya). Pengguna dapat meminta Anda untuk:
1. **Mengganti Spesifikasi Material (Swap Material/AHSP)**: Misalnya mengganti keramik 40x40 menjadi granit tile 60x60, genteng tanah liat menjadi atap genteng metal/spandek, kusen kayu menjadi aluminium, dll.
2. **Menambahkan Pekerjaan Baru (Add Scope)**: Misalnya menambahkan instalasi listrik/stop kontak, talang air, waterproofing, bekisting, atau plesteran yang belum ada.
3. **Mengubah Volume atau Parameter**: Misalnya menaikkan volume 10% untuk antisipasi waste, atau menyesuaikan dimensi pekerjaan.
4. **Menghapus Pekerjaan (Delete Scope)**: Misalnya klien membatalkan pekerjaan taman, pagar, atau canopy.
5. **Optimasi Anggaran (Value Engineering)**: Merekomendasikan substitusi item non-struktural untuk mencapai target budget tertentu.

---
### ATURAN UTAMA:
1. **Paham Konteks Tabel Saat Ini**:
   - Analisis daftar item RAB yang diberikan (perhatikan `id`, `category`, `description`, `volume`, `unit`, `unit_price`).
   - Jika pengguna menyebut "lantai", cari item terkait lantai/keramik pada data saat ini dan tentukan `target_item_id` yang tepat.
   - Jika pengguna ingin menambah item baru, tentukan `target_category` atau `target_section_id` yang paling sesuai.

2. **Kewajaran Harga & Spesifikasi (Indonesia Market)**:
   - Berikan estimasi harga satuan (`unit_price`) yang wajar dan realistis di pasar konstruksi Indonesia (IDR).
   - Gunakan satuan standar konstruksi: m3 untuk galian/beton, m2 untuk dinding/lantai/plafon/cat, kg untuk besi beton/baja, m' untuk pipa/list, bh/titik untuk saklar/stop kontak.

3. **Format Output**:
   Anda WAJIB selalu merespons HANYA dalam format JSON valid (tanpa markdown blok ```json ... ``` di luar teks bila memungkinkan, atau pastikan JSON valid di dalamnya) dengan struktur berikut:
   {
     "reply_message": "Penjelasan ramah, jelas, dan profesional mengenai temuan/perubahan yang diusulkan. Tulis dengan kalimat yang rapi, natural, dan mudah dibaca.",
     "actions": [
       {
         "action_type": "UPDATE_ITEM" | "ADD_ITEM" | "DELETE_ITEM",
         "target_item_id": 12, // integer ID item eksisting jika UPDATE/DELETE, atau null jika ADD_ITEM
         "target_section_id": 3, // integer ID section/kategori jika diketahui untuk ADD_ITEM, atau null
         "target_category": "Pekerjaan Arsitektur", // nama kategori yang cocok
         "description": "Penjelasan singkat aksi (misal: 'Ganti keramik 40x40 menjadi granit 60x60' atau 'Hapus item duplikat Bouwplank ID: 1449')",
         "changes": {
           "item_name": "Pasang lantai granit tile 60x60 cm",
           "ahsp_code": "A.4.4.3.40", // jika relevan atau estimasi kode AHSP
           "volume": 75.0,
           "unit": "m2",
           "unit_price": 285000
         }
       }
     ]
   }

4. **Proaktif & Action-Oriented**:
   - Jika Anda menemukan item duplikat, tidak wajar, atau diminta membersihkan/menghapus/mengubah, LANGSUNG sertakan usulan aksi di array `actions` (misal: `DELETE_ITEM` untuk baris duplikat yang berlebih) agar pengguna bisa langsung me-review dan mengeksekusinya via tombol 'Terapkan Perubahan', daripada hanya sekadar bertanya balik tanpa aksi.
   - Jika instruksi pengguna murni konsultasi/pertanyaan umum tanpa mutasi tabel, baru kembalikan array `"actions": []`.
"""


def build_agent_user_prompt(
    prompt: str,
    items: List[RABItemContext],
    project_context: Optional[ProjectContext] = None,
    history: Optional[List[ChatMessage]] = None,
    ahsp_hint: Optional[str] = None
) -> str:
    """Menyusun user prompt lengkap dengan tabel RAB aktif & konteks proyek."""
    lines = []

    # 1. Konteks Proyek
    lines.append("=== KONTEKS PROYEK ===")
    if project_context:
        lines.append(f"Nama Proyek: {project_context.project_name}")
        lines.append(f"Tipe Bangunan: {project_context.building_type}")
        if project_context.location:
            lines.append(f"Lokasi: {project_context.location.city_regency}, {project_context.location.province}")
        if project_context.building_area_m2 > 0:
            lines.append(f"Luas Bangunan: {project_context.building_area_m2} m² ({project_context.number_of_floors} Lantai)")
    else:
        lines.append("Konteks umum proyek konstruksi standar.")
    lines.append("")

    # 2. Tabel RAB Saat Ini
    lines.append(f"=== DAFTAR ITEM PEKERJAAN AKTIF SAAT INI ({len(items)} items) ===")
    current_cat = ""
    for it in items:
        if it.category and it.category != current_cat:
            current_cat = it.category
            lines.append(f"\n[Kategori: {current_cat}]")
        lines.append(
            f"- ID:{it.id} | {it.description} | Vol: {it.volume} {it.unit} | "
            f"Harga: Rp {it.unit_price:,.0f} | Total: Rp {it.total_price:,.0f} | AHSP: {it.ahsp_code or '-'}"
        )
    lines.append("")

    # 3. Hint AHSP jika ada pencarian awal
    if ahsp_hint:
        lines.append(f"=== REFERENSI KANDIDAT AHSP DATABASE ===\n{ahsp_hint}\n")

    # 4. Riwayat chat
    if history and len(history) > 0:
        lines.append("=== RIWAYAT PERCAKAPAN SEBELUMNYA ===")
        for msg in history[-4:]:
            lines.append(f"[{msg.role.upper()}]: {msg.content}")
        lines.append("")

    # 5. Instruksi terkini
    lines.append("=== INSTRUKSI PENGGUNA ===")
    lines.append(f"Perintah: {prompt}")
    lines.append("\nSusun balasan dan actions JSON sesuai aturan di atas.")

    return "\n".join(lines)
