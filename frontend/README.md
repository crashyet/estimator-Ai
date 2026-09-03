# ⚛️ Estimator Frontend Dashboard

Dashboard web modern untuk manajemen proyek konstruksi, visualisasi RAB, dan seleksi kandidat AHSP berbasis AI. Dibangun dengan **React 18 + Vite + TailwindCSS**.

---

## 🌟 Fitur & Halaman Utama

### 📋 Project Management (`src/pages/Project.jsx`)
- Daftar proyek aktif dengan badge status, estimasi anggaran, dan tanggal.
- **Cache Version Management**: Setiap update aplikasi secara otomatis membersihkan localStorage lama menggunakan key versi (`estimator_v9_cleared`) agar data stale tidak muncul.
- Navigasi ke halaman Anggaran per proyek via query param `?id=`.

### 💰 RAB / Anggaran Detail (`src/pages/Anggaran.jsx`)
- Tabel WBS interaktif dengan header seksi dan item pekerjaan.
- **Badge Confidence**: Indikator visual per item (`high` = hijau, `medium` = kuning, `unmapped` = abu/merah).
- **AI Candidate Popover**: Klik badge untuk melihat daftar `ahsp_candidates` dengan similarity score — pengguna dapat memilih atau mengganti mapping AHSP secara interaktif.
- Kalkulasi total biaya RAB secara real-time.
- Export ke Excel atau JSON.

### 📊 Laporan & Analitik (`src/pages/Laporan.jsx`)
- Visualisasi breakdown biaya per seksi WBS.
- Ringkasan statistik proyek.

---

## 📂 Struktur Direktori

```
frontend/
├── index.html               # Entry HTML
├── vite.config.js           # Konfigurasi Vite dev server
├── package.json             # Dependencies (React 18, Lucide, Tailwind)
├── eslint.config.js         # ESLint rules
└── src/
    ├── main.jsx             # React entry point
    ├── App.jsx              # Router & layout utama
    ├── index.css            # Global styles & Tailwind utilities
    ├── pages/
    │   ├── Project.jsx      # Halaman daftar proyek
    │   ├── Anggaran.jsx     # Halaman RAB detail & AI candidate UI
    │   └── Laporan.jsx      # Halaman laporan analitik & export
    └── components/          # Komponen reusable (modal, popover, tabel, dsb.)
```

---

## 🚀 Quick Start

```bash
cd frontend

# Install dependencies
npm install

# Jalankan dev server (accessible di seluruh jaringan lokal)
npm run dev -- --host
```

Aplikasi tersedia di **`http://localhost:5173`**.

---

## 🤖 Fitur AI Candidate Popover

Setiap item pekerjaan di halaman Anggaran dapat memiliki array `ahsp_candidates`:

```json
{
  "id": "item-A-1",
  "name": "Pembersihan Lapangan",
  "ahsp_status": "mapped_medium",
  "ahsp_candidates": [
    { "id_pekerjaan": "1.1.1.1", "nama_pekerjaan": "Pembersihan Lapangan", "satuan": "m2", "score": 0.87 },
    { "id_pekerjaan": "1.1.1.2", "nama_pekerjaan": "Pemotongan Pohon", "satuan": "m2", "score": 0.72 }
  ]
}
```

Jika `ahsp_status` adalah `mapped_medium` atau `unmapped`, badge item dapat diklik dan menampilkan **popover kandidat** agar pengguna dapat memilih mapping yang paling tepat.

> **Catatan Development**: Saat ini `getInitialData()` di `Anggaran.jsx` sudah dilengkapi dummy `ahsp_candidates` untuk keperluan pengujian UI tanpa backend. Ganti dengan respons API dinamis saat integrasi penuh.

---

## ⚙️ Konfigurasi `.env`

```env
VITE_API_BASE_URL=http://localhost:8200
```

---

## 📚 Dokumentasi Lanjutan

- **[../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)** — Alur data end-to-end dari backend ke frontend
- **[../README.md](../README.md)** — Dokumentasi utama proyek
