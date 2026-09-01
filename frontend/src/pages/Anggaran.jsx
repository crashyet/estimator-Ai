import React, { useState, useMemo, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import Navbar from '../components/Navbar';
import { Icons, Logo, BusinessAvatar } from '../components/Icons';

// Currency and numbers formatter
const formatRupiah = (value) => {
  return "Rp " + Math.abs(value).toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatNumber = (value) => {
  return value.toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Generates initial sections of data based strictly on latest_raw_pipeline.json (Rumah Tinggal Bpk. Heri Hidayat)
const getInitialData = () => {
  return [
    {
      id: "sec-A",
      type: "section",
      code: "A",
      name: "PEKERJAAN PERSIAPAN & TANAH"
    },
    {
      id: "item-A-1",
      type: "item",
      sectionCode: "A",
      no: 1,
      code: "A.1",
      name: "Pembersihan Lapangan",
      volume: 96.0,
      unit: "m2",
      unitPrice: 15000,
      ahsp_code: null,
      ahsp_name: null,
      ahsp_unit: null,
      ahsp_status: "unmapped",
      ahsp_score: 0.2827,
      ahsp_candidates: []
    },
    {
      id: "item-A-2",
      type: "item",
      sectionCode: "A",
      no: 2,
      code: "A.2",
      name: "Pemasangan Bouwplank",
      volume: 40.0,
      unit: "m1",
      unitPrice: 77364,
      ahsp_code: "1.1.4.2",
      ahsp_name: "Pasangan Bouwplank",
      ahsp_unit: "m1",
      ahsp_status: "mapped_high",
      ahsp_score: 0.811,
      ahsp_candidates: [
        { id_pekerjaan: "1.1.4.2", nama_pekerjaan: "Pasangan Bouwplank", satuan: "m1", score: 0.811 }
      ]
    },
    {
      id: "item-A-3",
      type: "item",
      sectionCode: "A",
      no: 3,
      code: "A.3",
      name: "Penggalian Tanah Pondasi Footplate",
      volume: 42.12,
      unit: "m3",
      unitPrice: 80608.5,
      ahsp_code: null,
      ahsp_name: null,
      ahsp_unit: null,
      ahsp_status: "unmapped",
      ahsp_score: 0.3501,
      ahsp_candidates: [
        { id_pekerjaan: "A.2.1", nama_pekerjaan: "Tebas tebang pohon/tumbuhan Ø > 75 cm (diameter diukur 1m diatas permukaan tanah)", satuan: "batang", score: 0.3501 },
        { id_pekerjaan: "A.2.2", nama_pekerjaan: "Pemasangan Tangki Toren Kap. 3 m3", satuan: "buah", score: 0.3501 },
        { id_pekerjaan: "A.2.3", nama_pekerjaan: "Pemasangan Tangki Toren Kap. 4 m3", satuan: "buah", score: 0.3501 }
      ]
    },
    {
      id: "sec-B",
      type: "section",
      code: "B",
      name: "PEKERJAAN STRUKTUR & PONDASI BETON BERTULANG"
    },
    {
      id: "item-B-1",
      type: "item",
      sectionCode: "B",
      no: 1,
      code: "B.1",
      name: "Pengecoran Lantai Kerja Footplate (t=10cm)",
      volume: 3.02,
      unit: "m3",
      unitPrice: 950000,
      ahsp_code: null,
      ahsp_name: null,
      ahsp_unit: null,
      ahsp_status: "unmapped",
      ahsp_score: 0.3509,
      ahsp_candidates: []
    },
    {
      id: "item-B-2",
      type: "item",
      sectionCode: "B",
      no: 2,
      code: "B.2",
      name: "Pengecoran Footplate Beton Bertulang",
      volume: 7.56,
      unit: "m3",
      unitPrice: 4200000,
      ahsp_code: null,
      ahsp_name: null,
      ahsp_unit: null,
      ahsp_status: "unmapped",
      ahsp_score: 0.3502,
      ahsp_candidates: []
    },
    {
      id: "item-B-3",
      type: "item",
      sectionCode: "B",
      no: 3,
      code: "B.3",
      name: "Pengecoran Sloof Beton 15x20 cm (S1)",
      volume: 1.2,
      unit: "m3",
      unitPrice: 4200000,
      ahsp_code: null,
      ahsp_name: null,
      ahsp_unit: null,
      ahsp_status: "unmapped",
      ahsp_score: 0.3501,
      ahsp_candidates: []
    },
    {
      id: "item-B-4",
      type: "item",
      sectionCode: "B",
      no: 4,
      code: "B.4",
      name: "Pengecoran Kolom Utama Lt. 1 & 2 (K1, K2, Kp)",
      volume: 3.42,
      unit: "m3",
      unitPrice: 4500000,
      ahsp_code: "2.2.2.1.3",
      ahsp_name: "Pemasangan Fondasi Batu Belah Mortar Tipe M (17,5 MPa) setara 1 SP : 2 PP, cara semi mekanis",
      ahsp_unit: "m3",
      ahsp_status: "mapped_medium",
      ahsp_score: 0.5801,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.2.1.3", nama_pekerjaan: "Pemasangan Fondasi Batu Belah Mortar Tipe M (17,5 MPa) setara 1 SP : 2 PP, cara semi mekanis", satuan: "m3", score: 0.5801 },
        { id_pekerjaan: "2.2.2.1.11", nama_pekerjaan: "Pemasangan Fondasi Batu Belah campuran 1 SP : 6 PP, cara semi mekanis", satuan: "m3", score: 0.5801 },
        { id_pekerjaan: "2.2.2.1.7", nama_pekerjaan: "Pemasangan Fondasi Batu Belah Mortar Tipe N (5,2 MPa) setara 1 SP : 4 PP, cara semi mekanis", satuan: "m3", score: 0.58 }
      ]
    },
    {
      id: "item-B-5",
      type: "item",
      sectionCode: "B",
      no: 5,
      code: "B.5",
      name: "Pengecoran Balok Struktur Lt. 1 & 2 (B1, B2, B3)",
      volume: 2.88,
      unit: "m3",
      unitPrice: 4500000,
      ahsp_code: "2.2.2.1.3",
      ahsp_name: "Pemasangan Fondasi Batu Belah Mortar Tipe M (17,5 MPa) setara 1 SP : 2 PP, cara semi mekanis",
      ahsp_unit: "m3",
      ahsp_status: "mapped_medium",
      ahsp_score: 0.5803,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.2.1.3", nama_pekerjaan: "Pemasangan Fondasi Batu Belah Mortar Tipe M (17,5 MPa) setara 1 SP : 2 PP, cara semi mekanis", satuan: "m3", score: 0.5803 },
        { id_pekerjaan: "2.2.2.1.11", nama_pekerjaan: "Pemasangan Fondasi Batu Belah campuran 1 SP : 6 PP, cara semi mekanis", satuan: "m3", score: 0.5802 }
      ]
    },
    {
      id: "item-B-6",
      type: "item",
      sectionCode: "B",
      no: 6,
      code: "B.6",
      name: "Pengecoran Plat Lantai 2 & Atap Dak",
      volume: 14.4,
      unit: "m3",
      unitPrice: 4800000,
      ahsp_code: "2.2.2.1.11",
      ahsp_name: "Pemasangan Fondasi Batu Belah campuran 1 SP : 6 PP, cara semi mekanis",
      ahsp_unit: "m3",
      ahsp_status: "mapped_medium",
      ahsp_score: 0.5801,
      ahsp_candidates: [
        { id_pekerjaan: "2.2.2.1.11", nama_pekerjaan: "Pemasangan Fondasi Batu Belah campuran 1 SP : 6 PP, cara semi mekanis", satuan: "m3", score: 0.5801 }
      ]
    },
    {
      id: "sec-C",
      type: "section",
      code: "C",
      name: "PEKERJAAN ARSITEKTUR & FINISHING"
    },
    {
      id: "item-C-1",
      type: "item",
      sectionCode: "C",
      no: 1,
      code: "C.1",
      name: "Pemasangan Dinding Bata Merah / Ringan",
      volume: 320.0,
      unit: "m2",
      unitPrice: 115000,
      ahsp_code: "3.6.4.2",
      ahsp_name: "Pemasangan Dinding Bata Ringan Tebal 10 cm dengan Mortar Siap Pakai",
      ahsp_unit: "m2",
      ahsp_status: "mapped_high",
      ahsp_score: 0.8182,
      ahsp_candidates: [
        { id_pekerjaan: "3.6.4.2", nama_pekerjaan: "Pemasangan Dinding Bata Ringan Tebal 10 cm dengan Mortar Siap Pakai", satuan: "m2", score: 0.8182 },
        { id_pekerjaan: "3.6.4.1", nama_pekerjaan: "Pemasangan Dinding Bata Ringan Tebal 7,5 cm dengan Mortar Siap Pakai", satuan: "m2", score: 0.8168 },
        { id_pekerjaan: "3.6.4.3", nama_pekerjaan: "Pemasangan Dinding Bata Ringan Tebal 20 cm dengan Mortar Siap Pakai", satuan: "m2", score: 0.8104 }
      ]
    },
    {
      id: "item-C-2",
      type: "item",
      sectionCode: "C",
      no: 2,
      code: "C.2",
      name: "Pemasangan Keramik Lantai 60x60 cm & 30x30 cm",
      volume: 150.0,
      unit: "m2",
      unitPrice: 185000,
      ahsp_code: "3.9.8.5",
      ahsp_name: "Pemasangan Lantai Keramik Ukuran 30 cm x 60 cm (1SP:2PP), Polished",
      ahsp_unit: "m2",
      ahsp_status: "mapped_high",
      ahsp_score: 0.9058,
      ahsp_candidates: [
        { id_pekerjaan: "3.9.8.5", nama_pekerjaan: "Pemasangan Lantai Keramik Ukuran 30 cm x 60 cm (1SP:2PP), Polished", satuan: "m2", score: 0.9058 },
        { id_pekerjaan: "3.9.8.13", nama_pekerjaan: "Pemasangan Lantai Keramik Ukuran 30 cm x 60 cm (1SP:2PP), Unpolished", satuan: "m2", score: 0.9048 },
        { id_pekerjaan: "3.9.8.4", nama_pekerjaan: "Pemasangan Lantai Keramik Ukuran 30 cm x 30 cm (1SP : 2PP), Polished", satuan: "m2", score: 0.8866 }
      ]
    },
    {
      id: "item-C-3",
      type: "item",
      sectionCode: "C",
      no: 3,
      code: "C.3",
      name: "Pemasangan Plafond Gypsum Board Rangka Hollow",
      volume: 150.0,
      unit: "m2",
      unitPrice: 95000,
      ahsp_code: "3.5.2.1",
      ahsp_name: "Pemasangan langit-langit (plafon) papan gypsum tebal 9 mm",
      ahsp_unit: "m2",
      ahsp_status: "mapped_high",
      ahsp_score: 0.6944,
      ahsp_candidates: [
        { id_pekerjaan: "3.5.2.1", nama_pekerjaan: "Pemasangan langit-langit (plafon) papan gypsum tebal 9 mm", satuan: "m2", score: 0.6944 }
      ]
    },
    {
      id: "item-C-4",
      type: "item",
      sectionCode: "C",
      no: 4,
      code: "C.4",
      name: "Pemasangan Pintu Utama PJ1",
      volume: 1.0,
      unit: "unit",
      unitPrice: 3500000,
      ahsp_code: "3.11.4.5",
      ahsp_name: "Pemasangan Engsel Pintu",
      ahsp_unit: "buah",
      ahsp_status: "mapped_high",
      ahsp_score: 0.6118,
      ahsp_candidates: [
        { id_pekerjaan: "3.11.4.5", nama_pekerjaan: "Pemasangan Engsel Pintu", satuan: "buah", score: 0.6118 },
        { id_pekerjaan: "10.1.7", nama_pekerjaan: "Produksi Panel P1", satuan: "buah", score: 0.5988 }
      ]
    },
    {
      id: "item-C-5",
      type: "item",
      sectionCode: "C",
      no: 5,
      code: "C.5",
      name: "Pemasangan Pintu & Jendela Aluminium Lainnya (PJ2, P1, P2, P3, P4)",
      volume: 12.0,
      unit: "unit",
      unitPrice: 1800000,
      ahsp_code: "10.1.9",
      ahsp_name: "Produksi Panel P3",
      ahsp_unit: "buah",
      ahsp_status: "mapped_high",
      ahsp_score: 0.6029,
      ahsp_candidates: [
        { id_pekerjaan: "10.1.9", nama_pekerjaan: "Produksi Panel P3", satuan: "buah", score: 0.6029 }
      ]
    },
    {
      id: "item-C-6",
      type: "item",
      sectionCode: "C",
      no: 6,
      code: "C.6",
      name: "Pemasangan Rangka Atap Baja Ringan C75 & Penutup Atap",
      volume: 96.0,
      unit: "m2",
      unitPrice: 240000,
      ahsp_code: "2.1.1.2",
      ahsp_name: "Pemasangan Atap Jurai/Limasan Rangka Atap Baja Ringan (Canai Dingin) Profil C75",
      ahsp_unit: "m2",
      ahsp_status: "mapped_high",
      ahsp_score: 0.8732,
      ahsp_candidates: [
        { id_pekerjaan: "2.1.1.2", nama_pekerjaan: "Pemasangan Atap Jurai/Limasan Rangka Atap Baja Ringan (Canai Dingin) Profil C75", satuan: "m2", score: 0.8732 },
        { id_pekerjaan: "2.1.1.1", nama_pekerjaan: "Pemasangan Atap Pelana Rangka Atap Baja Ringan (Canai Dingin) profil C75", satuan: "m2", score: 0.8691 }
      ]
    },
    {
      id: "sec-D",
      type: "section",
      code: "D",
      name: "PEKERJAAN UTILITAS & MEP"
    },
    {
      id: "item-D-1",
      type: "item",
      sectionCode: "D",
      no: 1,
      code: "D.1",
      name: "Pemasangan Titik Lampu & Saklar/Stop Kontak",
      volume: 28.0,
      unit: "titik",
      unitPrice: 175000,
      ahsp_code: "5.1.5.13",
      ahsp_name: "Pemasangan Instalasi Stop Kontak",
      ahsp_unit: "titik",
      ahsp_status: "mapped_high",
      ahsp_score: 0.7584,
      ahsp_candidates: [
        { id_pekerjaan: "5.1.5.13", nama_pekerjaan: "Pemasangan Instalasi Stop Kontak", satuan: "titik", score: 0.7584 },
        { id_pekerjaan: "5.1.5.12", nama_pekerjaan: "Pemasangan Stop Kontak AC", satuan: "titik", score: 0.6535 }
      ]
    },
    {
      id: "item-D-2",
      type: "item",
      sectionCode: "D",
      no: 2,
      code: "D.2",
      name: "Pemasangan Instalasi Pipa Air Bersih Ø3/4\"",
      volume: 35.0,
      unit: "m1",
      unitPrice: 45000,
      ahsp_code: "5.5.4.14",
      ahsp_name: "Pemasangan Pipa PVC AW ; DN. 1-1/4\" (32 mm) + Isolasi",
      ahsp_unit: "m",
      ahsp_status: "mapped_medium",
      ahsp_score: 0.5632,
      ahsp_candidates: [
        { id_pekerjaan: "5.5.4.14", nama_pekerjaan: "Pemasangan Pipa PVC AW ; DN. 1-1/4\" (32 mm) + Isolasi", satuan: "m", score: 0.5632 },
        { id_pekerjaan: "5.5.4.19", nama_pekerjaan: "Pemasangan Pipa PVC AW ; DN. 4\" (100 mm) + Isolasi", satuan: "m", score: 0.5627 },
        { id_pekerjaan: "6.4.3.4", nama_pekerjaan: "Pemasangan pipa PPR PN 10, DN. 1-1/4\" (32 mm)", satuan: "m", score: 0.5627 }
      ]
    },
    {
      id: "item-D-3",
      type: "item",
      sectionCode: "D",
      no: 3,
      code: "D.3",
      name: "Pembuatan Septictank & Sumur Resapan",
      volume: 1.0,
      unit: "unit",
      unitPrice: 6500000,
      ahsp_code: "2.4.4.3",
      ahsp_name: "Pemindahan Komponen untuk Kolom Pracetak ( ± 20 m)",
      ahsp_unit: "buah",
      ahsp_status: "mapped_medium",
      ahsp_score: 0.5811,
      ahsp_candidates: [
        { id_pekerjaan: "2.4.4.3", nama_pekerjaan: "Pemindahan Komponen untuk Kolom Pracetak ( ± 20 m)", satuan: "buah", score: 0.5811 },
        { id_pekerjaan: "2.4.5.3", nama_pekerjaan: "Ereksi komponen untuk pelat pracetak", satuan: "buah", score: 0.5809 }
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
        if (Array.isArray(parsed) && parsed.length > 0) {
          return parsed;
        }
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

  // Listen for returning toast message from PemetaanAhsp page
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

  // Controls state & Infinite Scroll
  const tableContainerRef = useRef(null);
  const [visibleLimit, setVisibleLimit] = useState(50);
  const [searchQuery, setSearchQuery] = useState("");
  const [ppnRate, setPpnRate] = useState(0);

  // Standard Modals state
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedRow, setSelectedRow] = useState(null);
  const [targetSectionCode, setTargetSectionCode] = useState("A");

  // AHSP Mapping State
  const [showAhspModal, setShowAhspModal] = useState(false);
  const [ahspTargetRow, setAhspTargetRow] = useState(null);
  const [ahspSearchQuery, setAhspSearchQuery] = useState("");
  const [ahspSearchResults, setAhspSearchResults] = useState([]);
  const [ahspCoreQuery, setAhspCoreQuery] = useState("");
  const [showMasterCollection, setShowMasterCollection] = useState(false);
  const [isSearchingAhsp, setIsSearchingAhsp] = useState(false);
  const [isMappingBatch, setIsMappingBatch] = useState(false);

  // Form states
  const [formData, setFormData] = useState({
    name: "",
    volume: 0,
    unit: "",
    unitPrice: 0
  });

  // Toast notification state
  const [toast, setToast] = useState({
    show: false,
    message: "",
    type: "success"
  });

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
      setCurrentPage(1);
      setSearchQuery("");
      setPpnRate(0);
      triggerToast("Cache berhasil dihapus & data estimasi diatur ulang ke default!", "success");
    }
  };

  // Export CSV
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

  // AHSP Dedicated Page Navigation
  const handleOpenAhspModal = (row) => {
    navigate(`/pemetaan-ahsp?id=${activeProjectId}`, { state: { projectId: activeProjectId, targetRow: row } });
  };

  const fetchAhspSearch = async (query) => {
    setIsSearchingAhsp(true);
    const PYTHON_API_BASE = typeof window !== 'undefined' ? `http://${window.location.hostname}:8200` : (import.meta.env.VITE_PYTHON_API_URL || 'http://localhost:8200');
    try {
      const url = query && query.trim()
        ? `${PYTHON_API_BASE}/api/ahsp/search?q=${encodeURIComponent(query)}&limit=100`
        : `${PYTHON_API_BASE}/api/ahsp/items?limit=500`;
      const res = await fetch(url);
      if (res.ok) {
        const data = await res.json();
        setAhspSearchResults(data.items || []);
        if (data.core_query) setAhspCoreQuery(data.core_query);
      } else {
        setAhspSearchResults([]);
      }
    } catch (err) {
      console.error("Error fetching AHSP search:", err);
      setAhspSearchResults([]);
    } finally {
      setIsSearchingAhsp(false);
    }
  };

  const handleSelectAhspItem = (selectedAhsp) => {
    if (!ahspTargetRow) return;

    const newRows = rows.map((r) => {
      if (r.id === ahspTargetRow.id) {
        return {
          ...r,
          code: selectedAhsp.id_pekerjaan,
          ahsp_code: selectedAhsp.id_pekerjaan,
          ahsp_name: selectedAhsp.nama_pekerjaan,
          ahsp_unit: selectedAhsp.satuan,
          unit: selectedAhsp.satuan || r.unit,
          ahsp_score: selectedAhsp.score || 1.0,
          ahsp_status: "mapped_high",
          ahsp_candidates: null
        };
      }
      return r;
    });

    setRows(newRows);
    setShowAhspModal(false);
    setAhspTargetRow(null);
    triggerToast(`Berhasil menghubungkan ke AHSP ${selectedAhsp.id_pekerjaan}!`, "success");
  };

  // Batch Map All Unmapped Items against AHSP Engine
  const handleBatchAhspMap = async () => {
    setIsMappingBatch(true);
    triggerToast("Proses pemetaan otomatis AHSP sedang berjalan...", "success");
    const PYTHON_API_BASE = typeof window !== 'undefined' ? `http://${window.location.hostname}:8200` : (import.meta.env.VITE_PYTHON_API_URL || 'http://localhost:8200');

    try {
      let mappedCount = 0;
      const updatedRows = await Promise.all(
        rows.map(async (r) => {
          if (r.type !== 'item') return r;

          try {
            const res = await fetch(`${PYTHON_API_BASE}/api/ahsp/map-item`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ item_name: r.name, item_unit: r.unit || '' })
            });

            if (res.ok) {
              const data = await res.json();
              if (data.ahsp_code && (data.ahsp_status === 'mapped_high' || data.ahsp_status === 'mapped_medium')) {
                mappedCount++;
                return {
                  ...r,
                  code: data.ahsp_code,
                  ahsp_code: data.ahsp_code,
                  ahsp_name: data.ahsp_name,
                  ahsp_unit: data.ahsp_unit,
                  ahsp_score: data.ahsp_score,
                  ahsp_status: data.ahsp_status,
                  ahsp_candidates: data.ahsp_candidates
                };
              }
            }
          } catch (e) {
            console.error("Item mapping error:", e);
          }
          return r;
        })
      );

      setRows(updatedRows);
      triggerToast(`Selesai! ${mappedCount} item pekerjaan berhasil diselaraskan dengan AHSP.`, "success");
    } catch (err) {
      console.error("Batch map error:", err);
      triggerToast("Gagal terhubung ke backend AHSP.", "warning");
    } finally {
      setIsMappingBatch(false);
    }
  };

  // Dynamic calculations
  const totalProjectPrice = useMemo(() => {
    return rows.reduce((sum, r) => {
      if (r.type === 'item') {
        return sum + (r.volume * (r.unitPrice || 0));
      }
      return sum;
    }, 0);
  }, [rows]);

  // Filter rows
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

  // Reset visible limit on search query or row change
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

  // Add / Edit / Delete handlers
  const handleOpenAddModal = (sectionCode) => {
    setTargetSectionCode(sectionCode);
    setFormData({ name: "", volume: 1, unit: "m2", unitPrice: 10000 });
    setShowAddModal(true);
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

  const handleOpenEditModal = (item) => {
    setSelectedRow(item);
    setFormData({
      name: item.name,
      volume: item.volume,
      unit: item.unit,
      unitPrice: item.unitPrice
    });
    setShowEditModal(true);
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

  const handleDeleteSection = (section) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus seluruh bagian "${section.code}. ${section.name}"?`)) {
      const newRows = rows.filter(r => {
        if (r.id === section.id) return false;
        if (r.type === 'item' && r.sectionCode === section.code) return false;
        return true;
      });

      setRows(newRows);
      triggerToast(`Bagian "${section.code}. ${section.name}" berhasil dihapus.`, "warning");
    }
  };

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      <Navbar onResetData={handleResetData} />

      {/* Main Title Section */}
      <div className="max-w-[1240px] mx-auto px-4 mt-6 no-print">
        <div className="w-full bg-[#f1faf2] border border-[#dff3e1] rounded-lg py-4 px-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
          <div>
            <span className="text-[10px] font-extrabold text-emerald-700 bg-emerald-100/50 px-2.5 py-1 rounded-full uppercase tracking-wider">
              Klien: {projectDetail.client}
            </span>
            <h1 className="text-base md:text-lg font-bold tracking-wide text-emerald-950 uppercase mt-2 select-none">
              {projectDetail.title}
            </h1>
          </div>

          <div className="flex flex-wrap items-center gap-2.5">
            {/* Export Excel (CSV) Button */}
            <button
              onClick={handleExportCSV}
              className="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center gap-1.5"
            >
              <Icons.Grid className="w-3.5 h-3.5" />
              Ekspor CSV
            </button>
          </div>
        </div>
      </div>

      {/* Main Workspace Container */}
      <main className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="bg-white rounded-xl shadow-xs border border-slate-100 p-6">

          {/* Controls Bar */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">


            {/* Right Controls: Search bar */}
            <div className="flex items-center gap-2.5 w-full sm:w-auto max-w-xs">
              <span className="text-[13px] text-slate-600 font-medium whitespace-nowrap">Cari Data:</span>
              <div className="relative w-full">
                <input
                  type="text"
                  placeholder="Cari nama pekerjaan..."
                  value={searchQuery}
                  onChange={(e) => {
                    setSearchQuery(e.target.value);
                    setCurrentPage(1);
                  }}
                  className="w-full bg-white border border-slate-200 rounded-md py-1.5 pl-3 pr-9 text-[13px] text-slate-700 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-2xs transition-colors"
                />
                <div className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                  <Icons.Search className="w-4 h-4" />
                </div>
              </div>
            </div>
          </div>

          {/* Table Container with Infinite Scroll */}
          <div
            ref={tableContainerRef}
            onScroll={handleTableScroll}
            className="overflow-x-auto overflow-y-auto max-h-[600px] rounded-lg border border-slate-200 shadow-3xs mb-4 relative custom-scrollbar bg-white"
          >
            <table className="w-full border-collapse text-left text-[13px]">
              <thead className="bg-[#009624] text-white font-semibold sticky top-0 z-10 shadow-xs">
                <tr>
                  <th scope="col" className="py-3 px-4 text-center w-12 bg-[#009624] select-none">No.</th>
                  <th scope="col" className="py-3 px-4 text-left min-w-[340px] bg-[#009624]">Uraian Pekerjaan & Standar AHSP</th>
                  <th scope="col" className="py-3 px-4 text-right w-24 bg-[#009624]">Volume</th>
                  <th scope="col" className="py-3 px-4 text-center w-20 bg-[#009624]">Satuan</th>
                  <th scope="col" className="py-3 px-4 text-center w-28 bg-[#009624]">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 bg-white">
                {displayedRows.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="py-12 text-center text-slate-400 font-medium">
                      Tidak ada data yang ditemukan.
                    </td>
                  </tr>
                ) : (
                  displayedRows.map((row) => {
                    if (row.type === 'section') {
                      return (
                        <tr key={row.id} className="bg-slate-50/70 hover:bg-slate-100/50 transition-colors font-bold text-slate-800 group">
                          <td className="py-3 px-4 text-center select-none">{row.code}</td>
                          <td colSpan={3} className="py-3 px-4 uppercase tracking-wide text-[12.5px] text-emerald-950">
                            {row.name}
                          </td>
                          <td className="py-3 px-4 text-center"></td>
                        </tr>
                      );
                    }

                    // Item row with AHSP Badge rendering (High >= 85%, Medium >= 65%)
                    const isHighMapped = row.ahsp_status === 'mapped_high' || (row.ahsp_score && row.ahsp_score >= 0.85);
                    const isMedMapped = row.ahsp_status === 'mapped_medium' || (row.ahsp_score && row.ahsp_score >= 0.65 && row.ahsp_score < 0.85);

                    // Formatted WBS Number for far-left column (e.g. A.1, A.2, A.1.1)
                    const wbsNumber = row.wbs_code || (row.sectionCode ? `${row.sectionCode}.${row.no}` : row.no);

                    // Title display: Standar AHSP on top if mapped, otherwise AI item name on top
                    const topTitle = row.ahsp_name || row.name;

                    return (
                      <tr key={row.id} className="hover:bg-slate-50/50 transition-all group duration-150">
                        {/* No. Column (WBS hierarchy code e.g. A.1, A.2) */}
                        <td className="py-3 px-4 text-center font-bold text-slate-700 select-none tabular-nums text-[12.5px]">
                          {wbsNumber}
                        </td>
                        {/* Uraian Pekerjaan Column */}
                        <td className="py-3 px-4 max-w-[420px]">
                          <div className="flex flex-col gap-1">
                            {/* Top: Standar AHSP name */}
                            <div className="flex items-center gap-2 flex-wrap">
                              <span className="font-semibold text-slate-800 break-words">{topTitle}</span>
                            </div>

                            {/* Bottom: Subtitle for cross-referencing */}
                            {row.ahsp_name && row.ahsp_name !== row.name && (
                              <span className="text-[11.5px] text-slate-500 flex items-center gap-1">
                                <span className="text-slate-400">Hasil Deteksi:</span>
                                <span className="font-medium text-slate-600">{row.name}</span>
                              </span>
                            )}
                          </div>
                        </td>
                        <td className="py-3 px-4 text-right tabular-nums font-medium text-slate-700">
                          {formatNumber(row.volume)}
                        </td>
                        <td className="py-3 px-4 text-center text-slate-500 font-semibold">{row.unit}</td>
                        <td className="py-3 px-4 text-center">
                          <div className="inline-flex items-center gap-1.5 bg-[#d2f3d5] px-2.5 py-1 rounded-full shadow-3xs group-hover:bg-[#c3eec7] transition-all">
                            <button
                              onClick={() => handleOpenAhspModal(row)}
                              className="w-6 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                              title="Pemetaan Item Pekerjaan AHSP"
                            >
                              <Icons.Book className="w-3.5 h-3.5" />
                            </button>

                            <button
                              onClick={() => handleDeleteItem(row)}
                              className="w-6 h-6 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition-transform hover:scale-105"
                              title="Hapus Item Pekerjaan"
                            >
                              <Icons.Trash className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>

            {/* Infinite Scroll Footer inside container */}
            {visibleLimit < filteredRows.length && (
              <div
                onClick={() => setVisibleLimit((prev) => Math.min(filteredRows.length, prev + 50))}
                className="py-2.5 bg-slate-50 border-t border-slate-200 text-center text-[11.5px] text-[#009624] font-semibold cursor-pointer hover:bg-slate-100 transition-colors"
              >
                ▼ Gulir ke bawah atau klik di sini untuk memuat data berikutnya (+50 baris)
              </div>
            )}

            {visibleLimit >= filteredRows.length && filteredRows.length > 0 && (
              <div className="py-2.5 bg-slate-50 border-t border-slate-200 text-center text-[11.5px] text-slate-500 font-semibold">
                ✓ Semuanya telah dimuat ({filteredRows.length.toLocaleString('id-ID')} baris data BOQ)
              </div>
            )}
          </div>

        </div>
      </main>

      {/* Toast Notification */}
      {toast.show && (
        <div className={`fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4.5 py-3 rounded-lg shadow-xl text-white font-medium transition-all transform translate-y-0 animate-bounce duration-300 ${toast.type === 'success'
          ? 'bg-emerald-600 border border-emerald-500'
          : toast.type === 'warning'
            ? 'bg-amber-600 border border-amber-500'
            : 'bg-red-600 border border-red-500'
          }`}>
          <Icons.Info className="w-5 h-5" />
          <span className="text-[13px]">{toast.message}</span>
        </div>
      )}

      {/* Modal - Add Item */}
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

      {/* Modal - Edit Item */}
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