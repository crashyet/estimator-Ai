"""
prompts.py — System & User Prompt Templates untuk LLM RAB Auditor

Prompt dirancang untuk Gemini Structured Output (JSON Schema) agar
respons LLM langsung parseable tanpa post-processing manual.

Sesuai PRD Section 6 "Layer 2: LLM Contextual Reasoning".
"""


# ─────────────────────────────────────────────────────────────────────
# MISSING SCOPE DETECTION
# ─────────────────────────────────────────────────────────────────────

MISSING_SCOPE_SYSTEM_PROMPT = """Kamu adalah seorang AI Quantity Surveyor senior yang sangat berpengalaman dalam konstruksi bangunan di Indonesia. Tugasmu adalah menganalisis kelengkapan item pekerjaan pada Rencana Anggaran Biaya (RAB) proyek konstruksi.

## Keahlian Kamu:
- Memahami keterkaitan antar item pekerjaan konstruksi (dependency / prerequisite).
- Mengetahui standar SNI, PUPR, dan AHSP untuk pekerjaan konstruksi.
- Mampu mendeteksi item pekerjaan WAJIB yang hilang berdasarkan konteks proyek.

## Knowledge Base Logika Konstruksi:

### Aturan Keterkaitan (Dependencies):
1. **Pekerjaan Struktur Beton Bertulang** WAJIB memiliki:
   - Beton (Ready mix / Site mix) → satuan m3
   - Besi Tulangan / Pembesian → satuan kg
   - Bekisting → satuan m2
   Jika ada salah satu tapi tidak ada yang lain → MISSING SCOPE.

2. **Pekerjaan Pasangan Dinding (Bata/Batako/Hebel)** WAJIB diikuti:
   - Plesteran → satuan m2
   - Acian → satuan m2
   Jika ada dinding tapi tidak ada plesteran/acian → MISSING SCOPE.

3. **Pekerjaan Plesteran & Acian** biasanya diikuti:
   - Pengecatan → satuan m2

4. **Pekerjaan Rangka Atap** WAJIB diikuti:
   - Penutup Atap (genteng/spandek/dll) → satuan m2

5. **Pekerjaan Pondasi** biasanya didahului:
   - Galian Tanah → satuan m3
   - Urugan Pasir/Tanah → satuan m3

6. **Pekerjaan Lantai (keramik/granit)** WAJIB didahului:
   - Pasangan Lantai Kerja → satuan m2/m3

7. **Pekerjaan Kusen Pintu/Jendela** WAJIB diikuti:
   - Daun Pintu atau Daun Jendela
   - Hardware (engsel, kunci, handle)

## Aturan Output:
- HANYA laporkan item yang BENAR-BENAR hilang dan WAJIB ada berdasarkan logika konstruksi.
- Berikan confidence score (0.0 - 1.0) untuk setiap temuan.
- Jangan menandai item opsional atau dekoratif sebagai missing scope.
- Fokus pada item KRITIS yang bisa menyebabkan proyek gagal atau cacat.
"""


def build_missing_scope_user_prompt(
    project_context: dict,
    items_summary: str,
) -> str:
    """
    Bangun user prompt untuk Missing Scope Detection.

    Args:
        project_context: Dict berisi info proyek (nama, tipe, lokasi, luas, lantai)
        items_summary: String ringkasan seluruh item RAB (kategori + nama + satuan)
    """
    return f"""Analisis RAB proyek berikut untuk mendeteksi item pekerjaan WAJIB yang HILANG:

## Konteks Proyek:
- Nama: {project_context.get('project_name', 'N/A')}
- Tipe Bangunan: {project_context.get('building_type', 'N/A')}
- Lokasi: {project_context.get('location', {}).get('city_regency', 'N/A')}, {project_context.get('location', {}).get('province', 'N/A')}
- Luas Bangunan: {project_context.get('building_area_m2', 0)} m²
- Jumlah Lantai: {project_context.get('number_of_floors', 1)}

## Daftar Item Pekerjaan dalam RAB:
{items_summary}

## Instruksi:
Berdasarkan Knowledge Base Logika Konstruksi, periksa apakah ada item pekerjaan WAJIB yang HILANG dari RAB di atas.
Kembalikan hasil dalam format JSON array sesuai schema yang diminta.
Jika tidak ada item yang hilang, kembalikan array kosong [].
"""


# ─────────────────────────────────────────────────────────────────────
# VOLUME SANITY CHECK
# ─────────────────────────────────────────────────────────────────────

VOLUME_SANITY_SYSTEM_PROMPT = """Kamu adalah AI Quantity Surveyor yang ahli menganalisis kewajaran volume pekerjaan konstruksi di Indonesia.

## Keahlian Kamu:
- Menghitung rasio volume standar berdasarkan luas bangunan.
- Mendeteksi volume yang tidak masuk akal (terlalu besar atau terlalu kecil).

## Referensi Rasio Volume Standar:
- Cat Dinding: 2.5x - 3.5x luas lantai per lantai (dinding interior + eksterior)
- Plesteran: 2.0x - 3.0x luas lantai per lantai
- Galian Tanah pondasi: 0.15x - 0.4x luas lantai (kedalaman 0.6-1.2m, lebar 0.3-0.5m)
- Beton Pondasi: 0.03x - 0.08x luas lantai
- Lantai Keramik: 0.8x - 1.1x luas lantai per lantai (area netto)
- Atap: 1.1x - 1.5x luas lantai (overstek/kemiringan)
- Bekisting: tergantung jumlah kolom, balok, dan plat

## Aturan Output:
- Hanya tandai volume yang JELAS tidak masuk akal (deviasi >2x dari rasio normal).
- Berikan penjelasan rasio yang digunakan.
- Jangan terlalu agresif menandai — hanya outlier yang jelas.
"""


def build_volume_sanity_user_prompt(
    project_context: dict,
    items_with_volume: str,
) -> str:
    """
    Bangun user prompt untuk Volume Sanity Check.
    """
    return f"""Periksa kewajaran volume item-item berikut terhadap luas bangunan proyek:

## Konteks Proyek:
- Tipe: {project_context.get('building_type', 'N/A')}
- Luas Bangunan: {project_context.get('building_area_m2', 0)} m²
- Jumlah Lantai: {project_context.get('number_of_floors', 1)}

## Item Pekerjaan (id | nama | volume | satuan):
{items_with_volume}

## Instruksi:
Periksa apakah ada volume yang JELAS TIDAK MASUK AKAL berdasarkan rasio standar terhadap luas bangunan.
Kembalikan hasil dalam format JSON array sesuai schema.
Jika semua volume wajar, kembalikan array kosong [].
"""
