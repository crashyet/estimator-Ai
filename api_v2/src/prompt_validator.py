"""
prompt_validator.py — Validasi dan Guardrail Relevansi Domain Konstruksi untuk Prompt Teks.

Memeriksa apakah prompt teks yang diinput pengguna benar-benar mendeskripsikan
konsep bangunan, pekerjaan konstruksi, renovasi, atau arsitektur/teknik sipil.
Mencegah LLM memproses teks asal-asalan, resep makanan, percakapan umum,
kode program, atau karakter acak (gibberish).
"""

import re
from typing import Tuple, Optional, List

# Minimum requirement constants
MIN_PROMPT_LENGTH = 15
MIN_WORD_COUNT = 3

# 1. Domain Keywords: Elemen & Konsep Konstruksi Bangunan (Indonesia & English)
CONSTRUCTION_KEYWORDS = {
    # Tipe & Fungsi Bangunan
    "rumah", "gedung", "ruko", "kantor", "gudang", "villa", "hotel", "masjid",
    "mushola", "musholla", "gereja", "sekolah", "kos", "kost", "kontrakan",
    "cafe", "kafe", "resto", "restoran", "pabrik", "workshop", "bengkel",
    "pos", "pos satpam", "gazebo", "pendopo", "kanopi", "canopy", "carport",
    "garasi", "carport", "parkir", "tempat parkir", "area parkir", "pagar", "gerbang", "kolam", "kolam renang", "drainase", "saluran",
    "jembatan", "jalan", "taman", "hunian", "bangunan", "proyek", "apartemen",
    "hall", "auditorium", "ruang", "kamar", "dapur", "toilet", "wc", "kamar mandi",
    "teras", "balkon", "dak", "rooftop", "basement", "fasad", "interior", "eksterior",

    # Elemen Struktur & Komponen Bangunan
    "pondasi", "fondasi", "footplat", "batu kali", "tiang pancang", "bore pile",
    "sloof", "kolom", "balok", "ringbalk", "ring balok", "plat lantai", "pelat",
    "lantai", "dinding", "tembok", "partisi", "sekat", "plesteran", "plester",
    "acian", "aci", "kusen", "pintu", "jendela", "ventilasi", "bouvenlight",
    "plafon", "plafond", "langit-langit", "gypsum", "kalsiboard", "grc",
    "atap", "genteng", "spandek", "alderon", "asbes", "seng", "nok", "bubungan",
    "talang", "kuda-kuda", "tangga", "railing", "rebar", "pembesian", "rangka",
    "bekisting", "besi beton", "wiremesh",

    # Material Konstruksi
    "beton", "bata", "hebel", "bata ringan", "batako", "semen", "mortar",
    "pasir", "batu", "batu belah", "split", "kerikil", "baja", "baja ringan",
    "hollow", "wf", "besi", "kayu", "papan", "triplek", "keramik", "granit",
    "marmer", "vinyl", "parket", "aluminium", "alumunium", "kaca", "cat",
    "coating", "waterproofing", "pipa", "pvc", "ppr", "galvanis",

    # Aktivitas & Istilah Konstruksi
    "bangun", "pembangunan", "renovasi", "renov", "perbaikan", "rehab",
    "bongkar", "pembongkaran", "gali", "galian", "urug", "urugan", "timbunan",
    "cor", "pengecoran", "pasang", "pemasangan", "instalasi", "arsitektur",
    "konstruksi", "desain", "rab", "wbs", "ahsp", "boq", "spek", "spesifikasi",
    "tahap pembangunan", "finishing",

    # Dimensi & Ruang
    "m2", "m3", "m²", "m³", "meter", "luas", "lebar", "panjang", "tinggi",
    "tingkat", "lantai 1", "lantai 2", "lantai 3", "2 lantai", "1 lantai",
    "3 lantai", "tipe 36", "tipe 45", "tipe 54", "tipe 70", "bentang", "dimensi",

    # Sanitasi & Elektrikal (MEP)
    "listrik", "lampu", "saklar", "stop kontak", "panel", "mcb", "titik lampu",
    "sanitasi", "plumbing", "septic tank", "septictank", "peresapan", "resapan",
    "kloset", "closet", "wastafel", "shower", "kran", "sink", "bak kontrol"
}

# 2. Blacklist Keywords: Topik Luar Domain (Kuliner, Hewan, Percakapan, IT, dll)
OFFTOPIC_KEYWORDS = {
    # Makanan / Resep / Kuliner
    "resep", "bumbu", "memasak", "masak", "goreng", "tumis", "rebus", "panggang",
    "lezat", "gurih", "pedas", "manis", "asin", "porsi", "sendok", "garpu",
    "piring", "makanan", "minuman", "kue", "roti", "ayam", "daging", "sapi",
    "ikan", "sayur", "bawang", "cabai", "kecap", "garam", "gula", "minyak goreng",

    # Hewan Peliharaan / Biologi Non-Konstruksi
    "kucing", "anjing", "kelinci", "burung", "ikan cupang", "hamster", "pakan",
    "kebun binatang", "satwa",

    # Percakapan Kosong / Sapaan Umum
    "halo", "hai", "selamat pagi", "selamat siang", "selamat malam", "apa kabar",
    "siapa kamu", "siapa namamu", "lagi apa", "lagi ngapain", "ceritakan lelucon",

    # Pemrograman / Komputer Non-Konstruksi
    "def ", "function", "javascript", "python", "php", "golang", "react",
    "script", "coding", "program", "algoritma", "database sql", "query select",

    # Otomotif Kendaraan
    "mobil", "motor", "sepeda", "honda", "yamaha", "toyota", "ban mobil",
    "bengkel motor", "knalpot", "oli mesin"
}


def is_gibberish(text: str) -> bool:
    """Mendeteksi apakah teks merupakan karakter acak atau keyboard mash."""
    clean = text.strip().lower()

    # 1. Terlalu banyak karakter berulang berurutan (misal 'aaaaaa', 'zzzzzz')
    if re.search(r'(.)\1{4,}', clean):
        return True

    # 2. Kata-kata tanpa vokal yang panjang (misal 'asdfghjkl', 'qwrtyp')
    words = re.findall(r'[a-zA-Z]+', clean)
    if words:
        long_vowelless = [w for w in words if len(w) >= 6 and not re.search(r'[aiueo]', w)]
        if len(long_vowelless) >= 1:
            return True

    # 3. Pola pengulangan token pendek yang konyol (misal 'asdasdasd', 'test test test')
    if len(clean) >= 10:
        unique_chars = len(set(clean.replace(" ", "")))
        if unique_chars <= 3:
            return True

    # 4. Hanya berisi angka atau simbol
    letters_count = len(re.findall(r'[a-zA-Z]', clean))
    if letters_count < 6:
        return True

    return False


def validate_construction_prompt(prompt_text: str) -> Tuple[bool, Optional[str]]:
    """
    Validasi menyeluruh terhadap prompt teks pengguna.

    Returns:
        (is_valid, error_message):
            - is_valid = True jika prompt relevan dengan konstruksi bangunan.
            - is_valid = False jika tidak valid, disertai penjelasan ramah.
    """
    if not prompt_text or not prompt_text.strip():
        return False, "Deskripsi konsep bangunan / prompt teks tidak boleh kosong. Harap berikan gambaran bangunan yang ingin diestimasi."

    clean_text = prompt_text.strip()

    # Cek 1: Panjang karakter minimal
    if len(clean_text) < MIN_PROMPT_LENGTH:
        return False, (
            f"Prompt terlalu singkat ({len(clean_text)} karakter, minimal {MIN_PROMPT_LENGTH} karakter). "
            "Mohon berikan deskripsi konsep bangunan yang lebih jelas (contoh: 'Rumah tinggal 2 lantai luas 120m2 dengan 3 kamar tidur')."
        )

    # Cek 2: Jumlah kata minimal
    words = clean_text.split()
    if len(words) < MIN_WORD_COUNT:
        return False, (
            f"Prompt terlalu singkat ({len(words)} kata, minimal {MIN_WORD_COUNT} kata). "
            "Harap jelaskan jenis bangunan, spesifikasi ruangan, atau pekerjaan yang ingin direncanakan."
        )

    # Cek 3: Karakter acak / gibberish
    if is_gibberish(clean_text):
        return False, (
            "Prompt terdeteksi berisi karakter acak atau tidak bermakna. "
            "Harap masukkan deskripsi konsep bangunan yang sebenarnya."
        )

    # Cek 4: Relevansi Domain Konstruksi vs Topik Luar Domain
    normalized_lower = clean_text.lower()

    # Ekstraksi kata-kata (token)
    tokens = set(re.findall(r'[a-zA-Z0-9²³]+', normalized_lower))

    # Cek kecocokan kata kunci konstruksi (single token dan phrase)
    matched_construction = []
    for kw in CONSTRUCTION_KEYWORDS:
        if " " in kw:
            if kw in normalized_lower:
                matched_construction.append(kw)
        else:
            if kw in tokens or kw in normalized_lower:
                matched_construction.append(kw)

    # Cek kecocokan kata kunci luar domain (off-topic)
    matched_offtopic = []
    for oftk in OFFTOPIC_KEYWORDS:
        if " " in oftk:
            if oftk in normalized_lower:
                matched_offtopic.append(oftk)
        else:
            if oftk in tokens:
                matched_offtopic.append(oftk)

    # Evaluasi: Jika ada kata kunci off-topic dan SAMA SEKALI tidak ada kata kunci konstruksi
    if matched_offtopic and not matched_construction:
        example_offtopic = ", ".join(f"'{k}'" for k in matched_offtopic[:3])
        return False, (
            f"Prompt terdeteksi di luar domain konstruksi bangunan (ditemukan topik non-konstruksi: {example_offtopic}). "
            "Sistem Estimator AI khusus dirancang untuk estimasi bangunan, rumah, ruko, atau pekerjaan renovasi. "
            "Contoh prompt yang tepat: 'Pembangunan rumah minimalis 2 lantai ukuran 8x15 meter dengan 3 kamar tidur, struktur beton bertulang, dan atap baja ringan'."
        )

    # Evaluasi: Jika SAMA SEKALI tidak ditemukan kata kunci atau konsep konstruksi
    if not matched_construction:
        return False, (
            "Prompt tidak mengandung indikasi atau kata kunci pekerjaan konstruksi bangunan "
            "(seperti rumah, ruko, gedung, lantai, kamar, dinding, atap, pondasi, beton, baja, renovasi, dll). "
            "Harap berikan deskripsi bangunan fisik atau renovasi yang ingin Anda estimasi anggarannya."
        )

    return True, None
