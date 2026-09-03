import React, { useState, useMemo, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../components/Navbar';
import { Icons } from '../components/Icons';
import SummaryHeaderCard from '../components/anggaran/SummaryHeaderCard';
import ExportToolbar from '../components/anggaran/ExportToolbar';
import WbsSectionTable from '../components/anggaran/WbsSectionTable';

// Currency and numbers formatter helpers
const formatNumber = (value) => {
  return value.toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Initial benchmark dataset
const getInitialData = () => {
  return [
    { id: "sec-A", type: "section", code: "A", name: "PEKERJAAN PERSIAPAN & TANAH" },
    {
      id: "item-A-1", type: "item", sectionCode: "A", no: 1, code: "A.1",
      name: "Pembersihan Lapangan", volume: 96.0, unit: "m2", unitPrice: 15000,
      ahsp_code: "4.2.6.1", ahsp_name: "Pembersihan (Penyapuan) Area Tanam", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.7619,
      ahsp_candidates: [
        { id_pekerjaan: "4.2.6.1", nama_pekerjaan: "Pembersihan (Penyapuan) Area Tanam", satuan: "m2", score: 0.7619 },
        { id_pekerjaan: "1.1.1.1", nama_pekerjaan: "Pembersihan Lapangan dan Perataan", satuan: "m2", score: 0.7420 },
        { id_pekerjaan: "1.1.1.2", nama_pekerjaan: "Penebasan / Pembersihan Semak Belukar", satuan: "m2", score: 0.6910 }
      ]
    },
    {
      id: "item-A-2", type: "item", sectionCode: "A", no: 2, code: "A.2",
      name: "Pemasangan Bouwplank", volume: 40.0, unit: "m1", unitPrice: 77364,
      ahsp_code: "1.1.4.2", ahsp_name: "Pasangan Bouwplank", ahsp_unit: "m1", ahsp_status: "mapped_high", ahsp_score: 0.8608,
      ahsp_candidates: [
        { id_pekerjaan: "1.1.4.2", nama_pekerjaan: "Pasangan Bouwplank", satuan: "m1", score: 0.8608 },
        { id_pekerjaan: "1.1.4.1", nama_pekerjaan: "Pengukuran dan Pemasangan Bouwplank", satuan: "m1", score: 0.8120 },
        { id_pekerjaan: "1.1.4.3", nama_pekerjaan: "Pemasangan Papan Duga (Bouwplank) Kayu", satuan: "m1", score: 0.7540 }
      ]
    },
    {
      id: "item-A-3", type: "item", sectionCode: "A", no: 3, code: "A.3",
      name: "Penggalian Tanah Pondasi Footplate", volume: 36.8, unit: "m3", unitPrice: 80608.5,
      ahsp_code: "1.2.1.1.4", ahsp_name: "Penggalian tanah biasa sedalam 1 s.d 2 m untuk volume s.d 200 m3 secara manual", ahsp_unit: "m3", ahsp_status: "mapped_medium", ahsp_score: 0.6447,
      ahsp_candidates: [
        { id_pekerjaan: "1.2.1.1.4", nama_pekerjaan: "Penggalian tanah biasa sedalam 1 s.d 2 m untuk volume s.d 200 m3 secara manual", satuan: "m3", score: 0.6447 },
        { id_pekerjaan: "1.2.1.1.1", nama_pekerjaan: "Penggalian tanah biasa sedalam 1 m secara manual", satuan: "m3", score: 0.6210 },
        { id_pekerjaan: "1.2.1.2.1", nama_pekerjaan: "Penggalian tanah keras sedalam 1 m secara manual", satuan: "m3", score: 0.5890 }
      ]
    },
    { id: "sec-B", type: "section", code: "B", name: "PEKERJAAN STRUKTUR & PONDASI" },
    {
      id: "item-B-1", type: "item", sectionCode: "B", no: 1, code: "B.1",
      name: "Pemasangan Pasir Urug Bawah Pondasi", volume: 1.66, unit: "m3", unitPrice: 185000,
      ahsp_code: "1.3.1.2", ahsp_name: "Urugan dengan pasir uruk untuk volume s.d 200 m3 tanpa pemadatan secara manual", ahsp_unit: "m3", ahsp_status: "mapped_high", ahsp_score: 0.7668,
      ahsp_candidates: [
        { id_pekerjaan: "1.3.1.2", nama_pekerjaan: "Urugan dengan pasir uruk untuk volume s.d 200 m3 tanpa pemadatan secara manual", satuan: "m3", score: 0.7668 },
        { id_pekerjaan: "1.3.1.1", nama_pekerjaan: "Pengurugan Pasir Urug Bawah Pondasi", satuan: "m3", score: 0.7250 },
        { id_pekerjaan: "1.3.1.3", nama_pekerjaan: "Urugan Pasir Urug Dipadatkan", satuan: "m3", score: 0.6840 }
      ]
    },
    {
      id: "item-B-2", type: "item", sectionCode: "B", no: 2, code: "B.2",
      name: "Pengecoran Lantai Kerja Footplate", volume: 3.31, unit: "m3", unitPrice: 950000,
      ahsp_code: "2.2.1.6.1", ahsp_name: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", ahsp_unit: "m3", ahsp_status: "mapped_medium", ahsp_score: 0.6326,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.6.1", nama_pekerjaan: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", satuan: "m3", score: 0.6326 },
        { id_pekerjaan: "2.2.1.1.1", nama_pekerjaan: "Pengecoran Beton Mutu f'c=7.4 MPa (K 100) Lantai Kerja", satuan: "m3", score: 0.6120 },
        { id_pekerjaan: "2.2.1.1.2", nama_pekerjaan: "Pengecoran Beton Mutu f'c=9.8 MPa (K 125)", satuan: "m3", score: 0.5980 }
      ]
    },
    {
      id: "item-B-3", type: "item", sectionCode: "B", no: 3, code: "B.3",
      name: "Pengecoran Pondasi Footplate", volume: 8.35, unit: "m3", unitPrice: 4200000,
      ahsp_code: "2.2.1.6.6", ahsp_name: "Pengecoran Beton Menggunakan Ready Mixed F'c 25 MPa", ahsp_unit: "m3", ahsp_status: "mapped_medium", ahsp_score: 0.6347,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.6.6", nama_pekerjaan: "Pengecoran Beton Menggunakan Ready Mixed F'c 25 MPa", satuan: "m3", score: 0.6347 },
        { id_pekerjaan: "2.2.1.6.5", nama_pekerjaan: "Pengecoran Beton Menggunakan Ready Mixed F'c 20 MPa", satuan: "m3", score: 0.6180 },
        { id_pekerjaan: "2.2.1.2.3", nama_pekerjaan: "Pembuatan Pondasi Beton Bertulang (K 225)", satuan: "m3", score: 0.5890 }
      ]
    },
    {
      id: "item-B-4", type: "item", sectionCode: "B", no: 4, code: "B.4",
      name: "Pengecoran Sloof Beton 15x20 cm", volume: 2.4, unit: "m3", unitPrice: 4200000,
      ahsp_code: "2.2.1.6.2", ahsp_name: "Pengecoran Beton Menggunakan Ready Mixed F'c 15 MPa", ahsp_unit: "m3", ahsp_status: "mapped_high", ahsp_score: 0.7814,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.6.2", nama_pekerjaan: "Pengecoran Beton Menggunakan Ready Mixed F'c 15 MPa", satuan: "m3", score: 0.7814 },
        { id_pekerjaan: "2.2.1.10.2", nama_pekerjaan: "Pembuatan Sloof Beton Bertulang 15x20 cm", satuan: "m1", score: 0.7420 },
        { id_pekerjaan: "2.2.1.6.1", nama_pekerjaan: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", satuan: "m3", score: 0.6950 }
      ]
    },
    {
      id: "item-B-5", type: "item", sectionCode: "B", no: 5, code: "B.5",
      name: "Pengecoran Kolom Struktur Lt. 1 & 2", volume: 5.4, unit: "m3", unitPrice: 4500000,
      ahsp_code: "2.2.1.6.1", ahsp_name: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", ahsp_unit: "m3", ahsp_status: "mapped_medium", ahsp_score: 0.6439,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.6.1", nama_pekerjaan: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", satuan: "m3", score: 0.6439 },
        { id_pekerjaan: "2.2.1.6.6", nama_pekerjaan: "Pengecoran Beton Menggunakan Ready Mixed F'c 25 MPa", satuan: "m3", score: 0.6210 },
        { id_pekerjaan: "2.2.1.10.1", nama_pekerjaan: "Pembuatan Kolom Praktis Beton Bertulang (11x11)", satuan: "m1", score: 0.5840 }
      ]
    },
    {
      id: "item-B-6", type: "item", sectionCode: "B", no: 6, code: "B.6",
      name: "Pengecoran Balok Struktur Lt. 1 & 2", volume: 4.8, unit: "m3", unitPrice: 4500000,
      ahsp_code: "2.2.1.6.1", ahsp_name: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", ahsp_unit: "m3", ahsp_status: "mapped_medium", ahsp_score: 0.6395,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.6.1", nama_pekerjaan: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", satuan: "m3", score: 0.6395 },
        { id_pekerjaan: "2.2.1.6.6", nama_pekerjaan: "Pengecoran Beton Menggunakan Ready Mixed F'c 25 MPa", satuan: "m3", score: 0.6150 },
        { id_pekerjaan: "2.2.1.10.3", nama_pekerjaan: "Pembuatan Balok Ring Beton Bertulang", satuan: "m1", score: 0.5790 }
      ]
    },
    {
      id: "item-B-7", type: "item", sectionCode: "B", no: 7, code: "B.7",
      name: "Pengecoran Plat Lantai 2 & Dak Atap", volume: 15.2, unit: "m3", unitPrice: 4800000,
      ahsp_code: "2.2.1.6.1", ahsp_name: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", ahsp_unit: "m3", ahsp_status: "mapped_medium", ahsp_score: 0.6486,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.6.1", nama_pekerjaan: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", satuan: "m3", score: 0.6486 },
        { id_pekerjaan: "2.2.1.6.6", nama_pekerjaan: "Pengecoran Beton Menggunakan Ready Mixed F'c 25 MPa", satuan: "m3", score: 0.6280 },
        { id_pekerjaan: "2.2.1.5.1", nama_pekerjaan: "Pengecoran Plat Lantai Beton Bertulang", satuan: "m3", score: 0.6010 }
      ]
    },
    {
      id: "item-B-8", type: "item", sectionCode: "B", no: 8, code: "B.8",
      name: "Pembuatan Tangga Beton", volume: 2.1, unit: "m3", unitPrice: 4500000,
      ahsp_code: "2.2.1.10.1", ahsp_name: "Pembuatan kolom praktis beton bertulang (11x11)", ahsp_unit: "m1", ahsp_status: "mapped_medium", ahsp_score: 0.6081,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.1.10.1", nama_pekerjaan: "Pembuatan kolom praktis beton bertulang (11x11)", satuan: "m1", score: 0.6081 },
        { id_pekerjaan: "2.2.1.6.1", nama_pekerjaan: "Pengecoran Beton menggunakan Ready Mixed (untuk Bangunan Gedung)", satuan: "m3", score: 0.5890 },
        { id_pekerjaan: "2.2.1.5.2", nama_pekerjaan: "Pembuatan Tangga Beton Bertulang", satuan: "m3", score: 0.5640 }
      ]
    },
    { id: "sec-C", type: "section", code: "C", name: "PEKERJAAN DINDING & FINISH" },
    {
      id: "item-C-1", type: "item", sectionCode: "C", no: 1, code: "C.1",
      name: "Pemasangan Dinding Bata Merah", volume: 240.0, unit: "m2", unitPrice: 185000,
      ahsp_code: "3.7.14", ahsp_name: "Pemasangan Finishing Dinding Siar Pasangan Bata Merah", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.9569,
      ahsp_candidates: [
        { id_pekerjaan: "3.7.14", nama_pekerjaan: "Pemasangan Finishing Dinding Siar Pasangan Bata Merah", satuan: "m2", score: 0.9569 },
        { id_pekerjaan: "3.7.1.1", nama_pekerjaan: "Pemasangan Dinding Bata Merah Tebal 1/2 Bata (1SP : 4PP)", satuan: "m2", score: 0.9120 },
        { id_pekerjaan: "3.7.1.2", nama_pekerjaan: "Pemasangan Dinding Bata Merah Tebal 1/2 Bata (1SP : 2PP)", satuan: "m2", score: 0.8840 }
      ]
    },
    {
      id: "item-C-2", type: "item", sectionCode: "C", no: 2, code: "C.2",
      name: "Plesteran dan Acian Dinding", volume: 480.0, unit: "m2", unitPrice: 95000,
      ahsp_code: "3.7.8", ahsp_name: "Pemasangan Acian", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.7917,
      ahsp_candidates: [
        { id_pekerjaan: "3.7.8", nama_pekerjaan: "Pemasangan Acian", satuan: "m2", score: 0.7917 },
        { id_pekerjaan: "3.7.2.1", nama_pekerjaan: "Plesteran 1 SP : 4 PP Tebal 15 mm", satuan: "m2", score: 0.7640 },
        { id_pekerjaan: "3.7.2.2", nama_pekerjaan: "Plesteran 1 SP : 2 PP Tebal 15 mm", satuan: "m2", score: 0.7320 }
      ]
    },
    { id: "sec-D", type: "section", code: "D", name: "PEKERJAAN KUSEN, PINTU, JENDELA & LANTAI" },
    {
      id: "item-D-1", type: "item", sectionCode: "D", no: 1, code: "D.1",
      name: "Pemasangan Pintu Utama (PJ1)", volume: 1.0, unit: "unit", unitPrice: 3500000,
      ahsp_code: "3.11.1.5", ahsp_name: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.7606,
      ahsp_candidates: [
        { id_pekerjaan: "3.11.1.5", nama_pekerjaan: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", satuan: "m2", score: 0.7606 },
        { id_pekerjaan: "3.11.1.1", nama_pekerjaan: "Pemasangan Kusen Pintu Kayu Kamper", satuan: "m3", score: 0.7240 },
        { id_pekerjaan: "3.11.1.4", nama_pekerjaan: "Pemasangan Daun Pintu Panel Kayu", satuan: "m2", score: 0.6980 }
      ]
    },
    {
      id: "item-D-2", type: "item", sectionCode: "D", no: 2, code: "D.2",
      name: "Pemasangan Pintu Kaca/Aluminium (PJ2)", volume: 1.0, unit: "unit", unitPrice: 2800000,
      ahsp_code: "3.11.1.5", ahsp_name: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.8988,
      ahsp_candidates: [
        { id_pekerjaan: "3.11.1.5", nama_pekerjaan: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", satuan: "m2", score: 0.8988 },
        { id_pekerjaan: "3.11.1.6", nama_pekerjaan: "Pemasangan Pintu Aluminium Kaca Double", satuan: "m2", score: 0.8410 },
        { id_pekerjaan: "3.11.1.2", nama_pekerjaan: "Pemasangan Kusen Pintu Aluminium 4 Inci", satuan: "m1", score: 0.7950 }
      ]
    },
    {
      id: "item-D-3", type: "item", sectionCode: "D", no: 3, code: "D.3",
      name: "Pemasangan Pintu P1 & P2", volume: 6.0, unit: "unit", unitPrice: 2200000,
      ahsp_code: "3.11.1.5", ahsp_name: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.7606,
      ahsp_candidates: [
        { id_pekerjaan: "3.11.1.5", nama_pekerjaan: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", satuan: "m2", score: 0.7606 },
        { id_pekerjaan: "3.11.1.4", nama_pekerjaan: "Pemasangan Daun Pintu Panel Kayu", satuan: "m2", score: 0.7250 },
        { id_pekerjaan: "3.11.1.2", nama_pekerjaan: "Pemasangan Kusen Pintu Aluminium 4 Inci", satuan: "m1", score: 0.6910 }
      ]
    },
    {
      id: "item-D-4", type: "item", sectionCode: "D", no: 4, code: "D.4",
      name: "Pemasangan Pintu Kamar Mandi (P3)", volume: 3.0, unit: "unit", unitPrice: 1200000,
      ahsp_code: "3.11.1.5", ahsp_name: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.7606,
      ahsp_candidates: [
        { id_pekerjaan: "3.11.1.5", nama_pekerjaan: "Pemasangan Pintu Kaca Tebal 6 mm Rangka Aluminium", satuan: "m2", score: 0.7606 },
        { id_pekerjaan: "3.11.1.7", nama_pekerjaan: "Pemasangan Pintu PVC Kamar Mandi", satuan: "unit", score: 0.7410 },
        { id_pekerjaan: "3.11.1.4", nama_pekerjaan: "Pemasangan Daun Pintu Panel Kayu", satuan: "m2", score: 0.6840 }
      ]
    },
    {
      id: "item-D-5", type: "item", sectionCode: "D", no: 5, code: "D.5",
      name: "Pemasangan Jendela J1, J2, J3, J4", volume: 8.0, unit: "unit", unitPrice: 1800000,
      ahsp_code: "3.11.2.3", ahsp_name: "Pemasangan Jendela Kaca uPVC 2 daun ukuran 1,150 m x 1,30 m", ahsp_unit: "buah", ahsp_status: "mapped_high", ahsp_score: 0.8157,
      ahsp_candidates: [
        { id_pekerjaan: "3.11.2.3", nama_pekerjaan: "Pemasangan Jendela Kaca uPVC 2 daun ukuran 1,150 m x 1,30 m", satuan: "buah", score: 0.8157 },
        { id_pekerjaan: "3.11.2.1", nama_pekerjaan: "Pemasangan Kusen Jendela Aluminium 3 Inci", satuan: "m1", score: 0.7850 },
        { id_pekerjaan: "3.11.2.2", nama_pekerjaan: "Pemasangan Jendela Kaca Tebal 5 mm", satuan: "m2", score: 0.7410 }
      ]
    },
    {
      id: "item-D-6", type: "item", sectionCode: "D", no: 6, code: "D.6",
      name: "Pemasangan Keramik Lantai 60x60 cm", volume: 110.0, unit: "m2", unitPrice: 240000,
      ahsp_code: "3.9.5.3", ahsp_name: "Pemasangan Lantai Ubin Granit Ukuran 60 cm x 60 cm (1SP : 2PP)", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.9417,
      ahsp_candidates: [
        { id_pekerjaan: "3.9.5.3", nama_pekerjaan: "Pemasangan Lantai Ubin Granit Ukuran 60 cm x 60 cm (1SP : 2PP)", satuan: "m2", score: 0.9417 },
        { id_pekerjaan: "3.9.5.1", nama_pekerjaan: "Pemasangan Lantai Keramik 60 cm x 60 cm Polished", satuan: "m2", score: 0.8980 },
        { id_pekerjaan: "3.9.5.2", nama_pekerjaan: "Pemasangan Lantai Keramik 50 cm x 50 cm", satuan: "m2", score: 0.8420 }
      ]
    },
    {
      id: "item-D-7", type: "item", sectionCode: "D", no: 7, code: "D.7",
      name: "Pemasangan Keramik Lantai 30x30 cm (WC & Teras)", volume: 18.0, unit: "m2", unitPrice: 180000,
      ahsp_code: "3.9.8.12", ahsp_name: "Pemasangan Lantai Keramik Ukuran 30 cm x 30 cm (1SP : 2PP), Unpolished", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.9522,
      ahsp_candidates: [
        { id_pekerjaan: "3.9.8.12", nama_pekerjaan: "Pemasangan Lantai Keramik Ukuran 30 cm x 30 cm (1SP : 2PP), Unpolished", satuan: "m2", score: 0.9522 },
        { id_pekerjaan: "3.9.8.11", nama_pekerjaan: "Pemasangan Lantai Keramik Ukuran 20 cm x 20 cm Unpolished", satuan: "m2", score: 0.8910 },
        { id_pekerjaan: "3.9.8.10", nama_pekerjaan: "Pemasangan Dinding Keramik 20 cm x 25 cm", satuan: "m2", score: 0.8240 }
      ]
    },
    { id: "sec-E", type: "section", code: "E", name: "PEKERJAAN PLAFON, ATAP & MEP" },
    {
      id: "item-E-1", type: "item", sectionCode: "E", no: 1, code: "E.1",
      name: "Pemasangan Plafon Gypsum + Rangka Hollow", volume: 130.0, unit: "m2", unitPrice: 175000,
      ahsp_code: "3.5.3.1", ahsp_name: "Pemasangan rangka besi hollow galvanis 40.40 mm, modul 60 x 60 cm untuk langit-langit (plafon)", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.8719,
      ahsp_candidates: [
        { id_pekerjaan: "3.5.3.1", nama_pekerjaan: "Pemasangan rangka besi hollow galvanis 40.40 mm, modul 60 x 60 cm untuk langit-langit (plafon)", satuan: "m2", score: 0.8719 },
        { id_pekerjaan: "3.5.3.2", nama_pekerjaan: "Pemasangan Langit-langit Gypsum Board 9 mm", satuan: "m2", score: 0.8420 },
        { id_pekerjaan: "3.5.3.3", nama_pekerjaan: "Pemasangan List Plafon Gypsum Profil", satuan: "m1", score: 0.7850 }
      ]
    },
    {
      id: "item-E-2", type: "item", sectionCode: "E", no: 2, code: "E.2",
      name: "Pemasangan Rangka Atap Baja Ringan C75", volume: 95.0, unit: "m2", unitPrice: 220000,
      ahsp_code: "2.1.1.2", ahsp_name: "Pemasangan Atap Jurai/Limasan Rangka Atap Baja Ringan (Canai Dingin) Profil C75", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.9348,
      ahsp_candidates: [
        { id_pekerjaan: "2.1.1.2", nama_pekerjaan: "Pemasangan Atap Jurai/Limasan Rangka Atap Baja Ringan (Canai Dingin) Profil C75", satuan: "m2", score: 0.9348 },
        { id_pekerjaan: "2.1.1.1", nama_pekerjaan: "Pemasangan Rangka Atap Baja Ringan Kuda-Kuda Canai Dingin", satuan: "m2", score: 0.9010 },
        { id_pekerjaan: "2.1.1.3", nama_pekerjaan: "Pemasangan Reng Baja Ringan Hat Section", satuan: "m2", score: 0.8240 }
      ]
    },
    {
      id: "item-E-3", type: "item", sectionCode: "E", no: 3, code: "E.3",
      name: "Pemasangan Penutup Atap Genteng Beton Flat", volume: 95.0, unit: "m2", unitPrice: 165000,
      ahsp_code: "3.1.1.4", ahsp_name: "Pemasangan Atap Genteng Beton", ahsp_unit: "m2", ahsp_status: "mapped_high", ahsp_score: 0.9602,
      ahsp_candidates: [
        { id_pekerjaan: "3.1.1.4", nama_pekerjaan: "Pemasangan Atap Genteng Beton", satuan: "m2", score: 0.9602 },
        { id_pekerjaan: "3.1.1.1", nama_pekerjaan: "Pemasangan Atap Genteng Keramik", satuan: "m2", score: 0.8920 },
        { id_pekerjaan: "3.1.1.5", nama_pekerjaan: "Pemasangan Nok Genteng Beton", satuan: "m1", score: 0.8410 }
      ]
    },
    {
      id: "item-E-4", type: "item", sectionCode: "E", no: 4, code: "E.4",
      name: "Pemasangan Titik Lampu & Saklar/Stop Kontak", volume: 24.0, unit: "titik", unitPrice: 250000,
      ahsp_code: "5.1.5.13", ahsp_name: "Pemasangan Instalasi Stop Kontak", ahsp_unit: "titik", ahsp_status: "mapped_high", ahsp_score: 0.8555,
      ahsp_candidates: [
        { id_pekerjaan: "5.1.5.13", nama_pekerjaan: "Pemasangan Instalasi Stop Kontak", satuan: "titik", score: 0.8555 },
        { id_pekerjaan: "5.1.5.11", nama_pekerjaan: "Pemasangan Instalasi Titik Lampu Utama NYM 3x2.5 mm2", satuan: "titik", score: 0.8410 },
        { id_pekerjaan: "5.1.5.12", nama_pekerjaan: "Pemasangan Saklar Ganda / Seri", satuan: "buah", score: 0.7950 }
      ]
    },
    {
      id: "item-E-5", type: "item", sectionCode: "E", no: 5, code: "E.5",
      name: "Pemasangan Pipa Air Bersih & Kotor", volume: 45.0, unit: "m1", unitPrice: 120000,
      ahsp_code: "6.4.4.3", ahsp_name: 'Pemasangan pipa BS MED CLASS, DN. 1" (25 mm)', ahsp_unit: "m", ahsp_status: "mapped_high", ahsp_score: 0.7381,
      ahsp_candidates: [
        { id_pekerjaan: "6.4.4.3", nama_pekerjaan: 'Pemasangan pipa BS MED CLASS, DN. 1" (25 mm)', satuan: "m", score: 0.7381 },
        { id_pekerjaan: "6.4.1.2", nama_pekerjaan: "Pemasangan Pipa PVC AW Diameter 3/4 Inci", satuan: "m1", score: 0.7120 },
        { id_pekerjaan: "6.4.1.4", nama_pekerjaan: "Pemasangan Pipa PVC AW Diameter 3 Inci (Air Kotor)", satuan: "m1", score: 0.6890 }
      ]
    },
    {
      id: "item-E-6", type: "item", sectionCode: "E", no: 6, code: "E.6",
      name: "Pembuatan Septic Tank & Sumur Resapan", volume: 1.0, unit: "set", unitPrice: 6500000,
      ahsp_code: "6.2.4.1", ahsp_name: "Pembuatan Sumur Resapan Air Limbah diameter 80 cm, t=100 cm (dengan Tutup Beton)", ahsp_unit: "buah", ahsp_status: "mapped_high", ahsp_score: 0.7789,
      ahsp_candidates: [
        { id_pekerjaan: "6.2.4.1", nama_pekerjaan: "Pembuatan Sumur Resapan Air Limbah diameter 80 cm, t=100 cm (dengan Tutup Beton)", satuan: "buah", score: 0.7789 },
        { id_pekerjaan: "6.2.4.2", nama_pekerjaan: "Pembuatan Sumur Resapan Air Limbah diameter 80 cm, t=100 cm (tanpa Tutup Beton)", satuan: "buah", score: 0.7744 },
        { id_pekerjaan: "6.6.1.1", nama_pekerjaan: "Pembuatan Sumur Resapan Air Hujan diameter 80 cm, t=100 cm", satuan: "buah", score: 0.7555 }
      ]
    }
  ];
};

const Anggaran = () => {
  const navigate = useNavigate();
  const queryParams = useMemo(() => new URLSearchParams(window.location.search), []);
  const projectId = queryParams.get('id') || '1';

  const [rows, setRows] = useState(() => {
    const saved = localStorage.getItem(`estimator_uploaded_rows_${projectId}`);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) return parsed;
      } catch (e) {
        console.error(e);
      }
    }
    const initial = getInitialData();
    localStorage.setItem(`estimator_uploaded_rows_${projectId}`, JSON.stringify(initial));
    return initial;
  });

  const [activeProjectId, setActiveProjectId] = useState(projectId);

  if (projectId !== activeProjectId) {
    setActiveProjectId(projectId);
    const saved = localStorage.getItem(`estimator_uploaded_rows_${projectId}`);
    setRows(saved ? JSON.parse(saved) : getInitialData());
  }

  useEffect(() => {
    localStorage.setItem(`estimator_uploaded_rows_${activeProjectId}`, JSON.stringify(rows));
  }, [rows, activeProjectId]);

  useEffect(() => {
    const toastMsg = sessionStorage.getItem('estimator_toast_msg');
    if (toastMsg) {
      triggerToast(toastMsg, 'success');
      sessionStorage.removeItem('estimator_toast_msg');
    }
  }, []);

  const projectDetail = useMemo(() => {
    const savedProjects = localStorage.getItem('estimator_projects');
    if (savedProjects) {
      try {
        const parsed = JSON.parse(savedProjects);
        const match = parsed.find(p => String(p.id) === String(projectId));
        if (match) return match;
      } catch (e) {
        console.error(e);
      }
    }
    return { title: `Proyek Estimasi #${projectId}`, client: 'PT Beecons' };
  }, [projectId]);

  const tableContainerRef = useRef(null);
  const [visibleLimit, setVisibleLimit] = useState(50);
  const [searchQuery, setSearchQuery] = useState("");

  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedRow, setSelectedRow] = useState(null);
  const [targetSectionCode, setTargetSectionCode] = useState("A");

  const [formData, setFormData] = useState({ name: "", volume: 0, unit: "", unitPrice: 0 });
  const [toast, setToast] = useState({ show: false, message: "", type: "success" });

  const triggerToast = (message, type = "success") => {
    setToast({ show: true, message, type });
    setTimeout(() => {
      setToast(prev => ({ ...prev, show: false }));
    }, 3500);
  };

  const handleResetData = () => {
    if (window.confirm("Apakah Anda yakin ingin menghapus seluruh cache dan mengatur ulang semua data ke estimasi awal?")) {
      localStorage.removeItem(`estimator_uploaded_rows_${projectId}`);
      localStorage.removeItem('estimator_projects');
      sessionStorage.clear();
      const freshData = getInitialData();
      setRows(freshData);
      setSearchQuery("");
      triggerToast("Cache berhasil dihapus & data estimasi diatur ulang ke default!", "success");
    }
  };

  const handleExportCSV = () => {
    let csvContent = "\ufeffsep=;\n";
    csvContent += "Kode AHSP;Uraian Pekerjaan;Volume;Satuan;Status AHSP\n";

    rows.forEach((row) => {
      if (row.type === 'section') {
        csvContent += `"${row.code}";"${row.name.toUpperCase()}";"";"";""\n`;
      } else {
        const formattedVolume = String(row.volume).replace('.', ',');
        const codeDisplay = row.ahsp_code || row.code || '';
        const statusDisplay = row.ahsp_status || 'Manual';
        csvContent += `"${codeDisplay}";"${row.name}";"${formattedVolume}";"${row.unit}";"${statusDisplay}"\n`;
      }
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);

    const cleanTitle = projectDetail.title.replace(/[^a-zA-Z0-9]/g, "_");
    link.setAttribute("download", `Estimasi_AHSP_${cleanTitle}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    triggerToast("Berhasil mengekspor data WBS & AHSP ke Excel (CSV)!", "success");
  };

  const handleOpenAhspModal = (row) => {
    navigate(`/pemetaan-ahsp?id=${activeProjectId}`, { state: { projectId: activeProjectId, targetRow: row } });
  };

  const filteredRows = useMemo(() => {
    if (!searchQuery.trim()) return rows;

    const query = searchQuery.toLowerCase();
    const result = [];
    let currentSecHeader = null;
    let secHasMatchingItems = false;

    rows.forEach((row) => {
      if (row.type === 'section') {
        currentSecHeader = row;
        secHasMatchingItems = false;
      } else {
        const nameMatch = row.name.toLowerCase().includes(query);
        const ahspNameMatch = (row.ahsp_name || '').toLowerCase().includes(query);

        if (nameMatch || ahspNameMatch) {
          if (currentSecHeader && !secHasMatchingItems) {
            result.push(currentSecHeader);
            secHasMatchingItems = true;
          }
          result.push(row);
        }
      }
    });

    return result;
  }, [rows, searchQuery]);

  useEffect(() => {
    setVisibleLimit(50);
  }, [searchQuery, rows]);

  const displayedRows = useMemo(() => {
    return filteredRows.slice(0, visibleLimit);
  }, [filteredRows, visibleLimit]);

  const handleTableScroll = (e) => {
    const { scrollTop, scrollHeight, clientHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 100) {
      if (visibleLimit < filteredRows.length) {
        setVisibleLimit((prev) => Math.min(filteredRows.length, prev + 50));
      }
    }
  };

  const handleAddItem = (e) => {
    e.preventDefault();
    if (!formData.name.trim()) return;

    const targetIdx = rows.findIndex(r => r.type === 'section' && r.code === targetSectionCode);
    if (targetIdx === -1) return;

    let sectionItemCount = 0;
    let insertIdx = targetIdx + 1;

    for (let i = targetIdx + 1; i < rows.length; i++) {
      if (rows[i].type === 'section') {
        insertIdx = i;
        break;
      }
      if (rows[i].type === 'item') {
        sectionItemCount++;
        insertIdx = i + 1;
      }
    }

    const newItem = {
      id: `item-${targetSectionCode}-${Date.now()}`,
      type: 'item',
      sectionCode: targetSectionCode,
      no: sectionItemCount + 1,
      code: `${targetSectionCode}.${sectionItemCount + 1}`,
      name: formData.name,
      volume: Number(formData.volume),
      unit: formData.unit,
      unitPrice: Number(formData.unitPrice)
    };

    const newRows = [...rows];
    newRows.splice(insertIdx, 0, newItem);

    setRows(newRows);
    setShowAddModal(false);
    triggerToast(`Berhasil menambahkan "${formData.name}" ke Bagian ${targetSectionCode}!`);
  };

  const handleEditItem = (e) => {
    e.preventDefault();
    if (!selectedRow) return;

    const newRows = rows.map(r => {
      if (r.id === selectedRow.id) {
        return {
          ...r,
          name: formData.name,
          volume: Number(formData.volume),
          unit: formData.unit,
          unitPrice: Number(formData.unitPrice)
        };
      }
      return r;
    });

    setRows(newRows);
    setShowEditModal(false);
    setSelectedRow(null);
    triggerToast(`Detail pekerjaan "${formData.name}" berhasil diperbarui.`);
  };

  const handleDeleteItem = (item) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus "${item.name}"?`)) {
      const newRows = rows.filter(r => r.id !== item.id);
      setRows(newRows);
      triggerToast(`Pekerjaan "${item.name}" berhasil dihapus.`, "warning");
    }
  };

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      <Navbar onResetData={handleResetData} />

      <SummaryHeaderCard
        projectDetail={projectDetail}
        handleExportCSV={handleExportCSV}
      />

      <main className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="bg-white rounded-xl shadow-xs border border-slate-100 p-6">
          <ExportToolbar
            searchQuery={searchQuery}
            setSearchQuery={setSearchQuery}
          />

          <WbsSectionTable
            tableContainerRef={tableContainerRef}
            handleTableScroll={handleTableScroll}
            displayedRows={displayedRows}
            filteredRows={filteredRows}
            visibleLimit={visibleLimit}
            setVisibleLimit={setVisibleLimit}
            handleOpenAhspModal={handleOpenAhspModal}
            handleDeleteItem={handleDeleteItem}
          />
        </div>
      </main>

      {toast.show && (
        <div className={`fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4.5 py-3 rounded-lg shadow-xl text-white font-medium transition-all transform translate-y-0 animate-bounce duration-300 ${
          toast.type === 'success'
            ? 'bg-emerald-600 border border-emerald-500'
            : toast.type === 'warning'
              ? 'bg-amber-600 border border-amber-500'
              : 'bg-red-600 border border-red-500'
        }`}>
          <Icons.Info className="w-5 h-5" />
          <span className="text-[13px]">{toast.message}</span>
        </div>
      )}

      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden transform scale-100 transition-all">
            <div className="bg-emerald-600 text-white px-5 py-4 flex items-center justify-between">
              <h3 className="font-bold text-[14.5px] uppercase tracking-wide">
                Tambah Pekerjaan (Bagian {targetSectionCode})
              </h3>
              <button
                onClick={() => setShowAddModal(false)}
                className="p-1 rounded-full hover:bg-emerald-700 text-emerald-100 hover:text-white transition-colors"
              >
                <Icons.X />
              </button>
            </div>

            <form onSubmit={handleAddItem} className="p-5 space-y-4">
              <div>
                <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                  Uraian Pekerjaan <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Misal: Pemasangan keramik lantai..."
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Volume <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    required
                    step="0.01"
                    min="0.01"
                    value={formData.volume}
                    onChange={(e) => setFormData({ ...formData, volume: Number(e.target.value) })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="m2, m3, unit, dll."
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
              </div>

              <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-2 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12.5px] font-semibold transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[12.5px] font-semibold shadow-sm transition-colors cursor-pointer"
                >
                  Simpan Pekerjaan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {showEditModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden transform scale-100 transition-all">
            <div className="bg-emerald-600 text-white px-5 py-4 flex items-center justify-between">
              <h3 className="font-bold text-[14.5px] uppercase tracking-wide">
                Ubah Detail Pekerjaan
              </h3>
              <button
                onClick={() => {
                  setShowEditModal(false);
                  setSelectedRow(null);
                }}
                className="p-1 rounded-full hover:bg-emerald-700 text-emerald-100 hover:text-white transition-colors"
              >
                <Icons.X />
              </button>
            </div>

            <form onSubmit={handleEditItem} className="p-5 space-y-4">
              <div>
                <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                  Uraian Pekerjaan <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Misal: Pemasangan keramik lantai..."
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Volume <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    required
                    step="0.01"
                    min="0.01"
                    value={formData.volume}
                    onChange={(e) => setFormData({ ...formData, volume: Number(e.target.value) })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-[12.5px] font-semibold text-slate-600 mb-1.5">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="m2, m3, unit, dll."
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="w-full bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors"
                  />
                </div>
              </div>

              <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => {
                    setShowEditModal(false);
                    setSelectedRow(null);
                  }}
                  className="px-4 py-2 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12.5px] font-semibold transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[12.5px] font-semibold shadow-sm transition-colors cursor-pointer"
                >
                  Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Anggaran;