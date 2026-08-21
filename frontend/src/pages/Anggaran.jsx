import React, { useState, useMemo, useEffect } from 'react';
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

// Generates initial sections of data
const getInitialData = () => {
  const sectionA = {
    code: 'A',
    name: 'PEKERJAAN PERSIAPAN',
    items: [
      { name: 'Pembersihan lapangan dan perataan', volume: 139.52, unit: 'm2', unitPrice: 13965 },
      { name: 'Pembuatan pagar sementara dari seng gelombang tinggi 2 meter', volume: 48.00, unit: 'm2', unitPrice: 212496.38 },
      { name: 'Pengukuran dan pemasangan Bouwplank', volume: 117.80, unit: 'm1', unitPrice: 77364 },
      { name: 'Penggalian tanah biasa sedalam 2 m', volume: 99.90, unit: 'm3', unitPrice: 80608.50 },
      { name: 'Pengurugan kembali galian tanah', volume: 27.66, unit: 'm3', unitPrice: 47565 },
      { name: 'Pembuatan gudang semen dan peralatan', volume: 9.00, unit: 'm2', unitPrice: 567606.38 },
      { name: 'Mengangkut tanah sisa galian', volume: 72.24, unit: 'm3', unitPrice: 17913 },
      { name: 'Pemadatan tanah tanah (per 20 cm)', volume: 32.40, unit: 'm3', unitPrice: 47565 },
      { name: 'Uji sondir dan hand boring', volume: 1.00, unit: 'paket', unitPrice: 3150000 }
    ]
  };

  const rawSectionsBToU = [
    {
      code: 'B',
      name: 'PEKERJAAN TANAH DAN PONDASI',
      items: [
        { name: 'Galian tanah pondasi menerus', volume: 120.00, unit: 'm3', unitPrice: 75000 },
        { name: 'Urugan pasir di bawah pondasi t=10cm', volume: 15.50, unit: 'm3', unitPrice: 180000 },
        { name: 'Pondasi batu belah 1:4', volume: 85.00, unit: 'm3', unitPrice: 850000 },
        { name: 'Pondasi bored pile dia. 30cm', volume: 160.00, unit: 'm1', unitPrice: 280000 },
        { name: 'Urugan tanah kembali bekas galian', volume: 40.00, unit: 'm3', unitPrice: 35000 },
        { name: 'Cerucuk bambu dia. 8-10cm L=3m', volume: 150.00, unit: 'batang', unitPrice: 25000 },
        { name: 'Lantai kerja beton tumbuk t=5cm', volume: 8.50, unit: 'm3', unitPrice: 950000 },
        { name: 'Urugan sirtu di bawah lantai padat', volume: 35.00, unit: 'm3', unitPrice: 220000 },
        { name: 'Pemadatan tanah area pondasi', volume: 120.00, unit: 'm2', unitPrice: 18000 }
      ]
    },
    {
      code: 'C',
      name: 'PEKERJAAN STRUKTUR BETON LANTAI 1',
      items: [
        { name: 'Sloof beton bertulang 15/20 K-250', volume: 12.50, unit: 'm3', unitPrice: 4200000 },
        { name: 'Kolom beton bertulang 30/30 K-300', volume: 8.40, unit: 'm3', unitPrice: 4500000 },
        { name: 'Balok beton bertulang 20/40 K-300', volume: 10.20, unit: 'm3', unitPrice: 4500000 },
        { name: 'Plat lantai beton bertulang t=12cm K-300', volume: 18.50, unit: 'm3', unitPrice: 4800000 },
        { name: 'Kolom praktis 15/15 K-175', volume: 4.80, unit: 'm3', unitPrice: 3500000 },
        { name: 'Ring balk 15/20 K-175', volume: 5.20, unit: 'm3', unitPrice: 3800000 },
        { name: 'Tangga beton bertulang K-250', volume: 3.50, unit: 'm3', unitPrice: 4300000 },
        { name: 'Bekisting kayu kruing untuk sloof', volume: 45.00, unit: 'm2', unitPrice: 180000 },
        { name: 'Kawat beton / bendrat', volume: 85.00, unit: 'kg', unitPrice: 22000 }
      ]
    },
    {
      code: 'D',
      name: 'PEKERJAAN STRUKTUR BETON LANTAI 2',
      items: [
        { name: 'Kolom beton bertulang 30/30 K-300', volume: 7.20, unit: 'm3', unitPrice: 4600000 },
        { name: 'Balok beton bertulang 20/40 K-300', volume: 9.80, unit: 'm3', unitPrice: 4600000 },
        { name: 'Plat lantai beton bertulang t=12cm K-300', volume: 16.50, unit: 'm3', unitPrice: 4900000 },
        { name: 'Kolom praktis 15/15 K-175', volume: 4.20, unit: 'm3', unitPrice: 3600000 },
        { name: 'Ring balk 15/20 K-175', volume: 4.80, unit: 'm3', unitPrice: 3900000 },
        { name: 'Bekisting plywood 9mm untuk balok', volume: 120.00, unit: 'm2', unitPrice: 165000 },
        { name: 'Bekisting plywood 9mm untuk plat', volume: 150.00, unit: 'm2', unitPrice: 175000 },
        { name: 'Besi beton ulir D13', volume: 850.00, unit: 'kg', unitPrice: 16500 },
        { name: 'Besi beton polos d8', volume: 420.00, unit: 'kg', unitPrice: 15500 }
      ]
    },
    {
      code: 'E',
      name: 'PEKERJAAN STRUKTUR BETON LANTAI 3',
      items: [
        { name: 'Kolom beton bertulang 25/25 K-300', volume: 5.40, unit: 'm3', unitPrice: 4700000 },
        { name: 'Balok beton bertulang 20/35 K-300', volume: 8.20, unit: 'm3', unitPrice: 4700000 },
        { name: 'Plat atap beton bertulang t=10cm K-300', volume: 12.00, unit: 'm3', unitPrice: 5000000 },
        { name: 'Kolom praktis 15/15 K-175', volume: 3.60, unit: 'm3', unitPrice: 3700000 },
        { name: 'Ring balk 15/20 K-175', volume: 4.20, unit: 'm3', unitPrice: 4000000 },
        { name: 'Bekisting plywood 9mm untuk kolom', volume: 85.00, unit: 'm2', unitPrice: 185000 },
        { name: 'Besi beton ulir D13', volume: 680.00, unit: 'kg', unitPrice: 16500 },
        { name: 'Besi beton polos d8', volume: 310.00, unit: 'kg', unitPrice: 15500 },
        { name: 'Pekerjaan curing beton plat atap', volume: 1.00, unit: 'ls', unitPrice: 1200000 }
      ]
    },
    {
      code: 'F',
      name: 'PEKERJAAN DINDING DAN PLESTERAN',
      items: [
        { name: 'Pasang dinding bata merah tebal 1/2 bata 1:4', volume: 480.00, unit: 'm2', unitPrice: 115000 },
        { name: 'Pasang dinding bata merah tebal 1/2 bata 1:2', volume: 95.00, unit: 'm2', unitPrice: 125000 },
        { name: 'Plesteran dinding tebal 15mm 1:4', volume: 960.00, unit: 'm2', unitPrice: 65000 },
        { name: 'Plesteran dinding tebal 15mm 1:2', volume: 190.00, unit: 'm2', unitPrice: 72000 },
        { name: 'Acian dinding plesteran interior', volume: 960.00, unit: 'm2', unitPrice: 38000 },
        { name: 'Acian dinding plesteran eksterior', volume: 190.00, unit: 'm2', unitPrice: 42000 },
        { name: 'Pasangan roster semen 20x20', volume: 85.00, unit: 'unit', unitPrice: 35000 },
        { name: 'Pasangan glass block 20x20', volume: 40.00, unit: 'unit', unitPrice: 65000 },
        { name: 'Pekerjaan tali air plesteran', volume: 180.00, unit: 'm1', unitPrice: 15000 }
      ]
    }
  ];

  const targetBToU = 954247569.57;
  let rawSumBToU = 0;
  rawSectionsBToU.forEach(sec => {
    sec.items.forEach(it => {
      rawSumBToU += it.volume * it.unitPrice;
    });
  });

  const factor = targetBToU / rawSumBToU;
  let currentSumBToU = 0;
  const scaledSections = rawSectionsBToU.map((sec, secIdx) => {
    const isLastSec = secIdx === rawSectionsBToU.length - 1;
    const scaledItems = sec.items.map((it, itIdx) => {
      const isLastItem = isLastSec && itIdx === sec.items.length - 1;
      if (isLastItem) return { ...it };
      const newPrice = Math.round((it.unitPrice * factor) * 100) / 100;
      currentSumBToU += it.volume * newPrice;
      return { ...it, unitPrice: newPrice };
    });
    return { ...sec, items: scaledItems };
  });

  const flattened = [];
  flattened.push({
    id: `sec-${sectionA.code}`,
    type: 'section',
    code: sectionA.code,
    name: sectionA.name
  });
  sectionA.items.forEach((it, idx) => {
    flattened.push({
      id: `item-${sectionA.code}-${idx}`,
      type: 'item',
      sectionCode: sectionA.code,
      no: idx + 1,
      name: it.name,
      volume: it.volume,
      unit: it.unit,
      unitPrice: it.unitPrice,
      code: `A.${idx + 1}`
    });
  });

  scaledSections.forEach(sec => {
    flattened.push({
      id: `sec-${sec.code}`,
      type: 'section',
      code: sec.code,
      name: sec.name
    });
    sec.items.forEach((it, idx) => {
      flattened.push({
        id: `item-${sec.code}-${idx}`,
        type: 'item',
        sectionCode: sec.code,
        no: idx + 1,
        name: it.name,
        volume: it.volume,
        unit: it.unit,
        unitPrice: it.unitPrice,
        code: `${sec.code}.${idx + 1}`
      });
    });
  });

  return flattened;
};

const Anggaran = () => {
  const queryParams = useMemo(() => new URLSearchParams(window.location.search), []);
  const projectId = queryParams.get('id') || '1';

  const [rows, setRows] = useState(() => {
    const saved = localStorage.getItem(`estimator_uploaded_rows_${projectId}`);
    return saved ? JSON.parse(saved) : getInitialData();
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

  // Controls state
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [searchQuery, setSearchQuery] = useState("");
  const [ppnRate, setPpnRate] = useState(0);

  // Standard Modals state
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedRow, setSelectedRow] = useState(null);
  const [targetSectionCode, setTargetSectionCode] = useState("A");

  // AHSP Mapping Modal State
  const [showAhspModal, setShowAhspModal] = useState(false);
  const [ahspTargetRow, setAhspTargetRow] = useState(null);
  const [ahspSearchQuery, setAhspSearchQuery] = useState("");
  const [ahspSearchResults, setAhspSearchResults] = useState([]);
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
    if (window.confirm("Apakah Anda yakin ingin mengatur ulang semua data ke estimasi awal?")) {
      setRows(getInitialData());
      setCurrentPage(1);
      setSearchQuery("");
      setPpnRate(0);
      triggerToast("Data estimasi berhasil diatur ulang ke default!", "success");
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

  // AHSP Live Search & Selection Logic
  const handleOpenAhspModal = (row) => {
    setAhspTargetRow(row);
    setAhspSearchQuery(row.name);
    setShowAhspModal(true);
    searchAhspItems(row.name, row.unit);
  };

  const searchAhspItems = async (query, unit = "") => {
    setIsSearchingAhsp(true);
    try {
      const res = await fetch(`http://localhost:8200/api/ahsp/search?q=${encodeURIComponent(query)}&top_k=15`);
      if (res.ok) {
        const data = await res.json();
        setAhspSearchResults(data.results || []);
      } else {
        setAhspSearchResults([]);
      }
    } catch (err) {
      console.error("Error searching AHSP:", err);
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

    try {
      let mappedCount = 0;
      const updatedRows = await Promise.all(
        rows.map(async (r) => {
          if (r.type !== 'item') return r;

          try {
            const res = await fetch('http://localhost:8200/api/ahsp/map-item', {
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
        const codeMatch = (row.code || '').toLowerCase().includes(query);
        const ahspCodeMatch = (row.ahsp_code || '').toLowerCase().includes(query);
        const ahspNameMatch = (row.ahsp_name || '').toLowerCase().includes(query);
        const unitMatch = row.unit.toLowerCase().includes(query);

        if (nameMatch || codeMatch || ahspCodeMatch || ahspNameMatch || unitMatch) {
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

  const totalItems = filteredRows.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const activePage = Math.min(currentPage, totalPages);

  const paginatedRows = useMemo(() => {
    const startIdx = (activePage - 1) * pageSize;
    return filteredRows.slice(startIdx, startIdx + pageSize);
  }, [filteredRows, activePage, pageSize]);

  const paginationRange = useMemo(() => {
    if (totalPages <= 7) {
      return Array.from({ length: totalPages }, (_, i) => i + 1);
    }
    const range = [];
    if (activePage <= 4) {
      range.push(1, 2, 3, 4, 5, '...', totalPages);
    } else if (activePage >= totalPages - 3) {
      range.push(1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages);
    } else {
      range.push(1, '...', activePage - 1, activePage, activePage + 1, '...', totalPages);
    }
    return range;
  }, [activePage, totalPages]);

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
            {/* Batch AHSP AI Mapping Button */}
            <button
              onClick={handleBatchAhspMap}
              disabled={isMappingBatch}
              className="bg-emerald-800 hover:bg-emerald-900 disabled:opacity-50 text-white px-3.5 py-2 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center gap-1.5"
              title="Petakan semua item secara otomatis ke AHSP standar Indonesia menggunakan Vector DB"
            >
              <Icons.Sparkles className="w-3.5 h-3.5 text-emerald-300 animate-pulse" />
              {isMappingBatch ? 'Memetakan...' : 'Standardkan AHSP (AI)'}
            </button>

            {/* Export Excel (CSV) Button */}
            <button
              onClick={handleExportCSV}
              className="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center gap-1.5"
            >
              <Icons.Grid className="w-3.5 h-3.5" />
              Ekspor CSV
            </button>

            {/* Print/PDF Button */}
            <button
              onClick={() => window.print()}
              className="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center gap-1.5"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2.5" stroke="currentColor" className="w-3.5 h-3.5 text-slate-500">
                <path strokeLinecap="round" strokeLinejoin="round" d="M6.72 13.82l-.24-2.58a.75.75 0 01.69-.81H16.8a.75.75 0 01.69.81l-.24 2.58m-10.53 0H16.8M4.5 18.75h15a2.25 2.25 0 002.25-2.25V9.75A2.25 2.25 0 0019.5 7.5h-15A2.25 2.25 0 002.25 9.75v6.75a2.25 2.25 0 002.25 2.25z" />
              </svg>
              Cetak
            </button>
          </div>
        </div>
      </div>

      {/* Main Workspace Container */}
      <main className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="bg-white rounded-xl shadow-xs border border-slate-100 p-6">

          {/* Controls Bar */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">
            {/* Left Controls: Page size */}
            <div className="flex items-center gap-2">
              <span className="text-[13px] text-slate-600 font-medium">Data per Halaman:</span>
              <div className="relative">
                <select
                  value={pageSize}
                  onChange={(e) => {
                    setPageSize(Number(e.target.value));
                    setCurrentPage(1);
                  }}
                  className="bg-white border border-slate-200 rounded-md py-1.5 pl-3 pr-8 text-[13px] font-semibold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer shadow-2xs hover:border-slate-300 transition-colors"
                >
                  <option value={5}>5</option>
                  <option value={10}>10</option>
                  <option value={20}>20</option>
                  <option value={50}>50</option>
                </select>
                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                  <Icons.ChevronDown />
                </div>
              </div>
            </div>

            {/* Right Controls: Search bar */}
            <div className="flex items-center gap-2.5 w-full sm:w-auto max-w-xs">
              <span className="text-[13px] text-slate-600 font-medium whitespace-nowrap">Cari Data:</span>
              <div className="relative w-full">
                <input
                  type="text"
                  placeholder="Masukan nama / kode AHSP..."
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

          {/* Table Container */}
          <div className="overflow-x-auto rounded-lg border border-slate-100 shadow-3xs mb-6">
            <table className="w-full border-collapse text-left text-[13px]">
              <thead>
                <tr className="bg-[#009624] text-white font-semibold">
                  <th scope="col" className="py-3.5 px-4 text-center w-12 select-none">No.</th>
                  <th scope="col" className="py-3.5 px-4 text-left min-w-[340px]">Uraian Pekerjaan & Standar AHSP</th>
                  <th scope="col" className="py-3.5 px-4 text-right w-24">Volume</th>
                  <th scope="col" className="py-3.5 px-4 text-center w-20">Satuan</th>
                  <th scope="col" className="py-3.5 px-4 text-center w-28">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {paginatedRows.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="py-12 text-center text-slate-400 font-medium">
                      Tidak ada data yang ditemukan.
                    </td>
                  </tr>
                ) : (
                  paginatedRows.map((row) => {
                    if (row.type === 'section') {
                      return (
                        <tr key={row.id} className="bg-slate-50/70 hover:bg-slate-100/50 transition-colors font-bold text-slate-800 group">
                          <td className="py-3 px-4 text-center select-none">{row.code}</td>
                          <td colSpan={3} className="py-3 px-4 uppercase tracking-wide text-[12.5px] text-emerald-950">
                            {row.name}
                          </td>
                          <td className="py-3 px-4 text-center">
                            <div className="inline-flex items-center gap-1.5 bg-[#c9f0cc] px-2.5 py-1 rounded-full shadow-2xs">
                              <button
                                onClick={() => handleOpenAddModal(row.code)}
                                className="w-6 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                                title={`Tambah pekerjaan ke Bagian ${row.code}`}
                              >
                                <Icons.Plus className="w-3.5 h-3.5 stroke-[3]" />
                              </button>
                              <button
                                onClick={() => handleDeleteSection(row)}
                                className="w-6 h-6 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                                title={`Hapus seluruh Bagian ${row.code}`}
                              >
                                <Icons.Trash className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </td>
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
                            {/* Top: Standar AHSP name + AHSP Code Badge */}
                            <div className="flex items-center gap-2 flex-wrap">
                              <span className="font-semibold text-slate-800 break-words">{topTitle}</span>
                              
                              {/* AHSP Badge */}
                              {row.ahsp_code || (row.code && row.code.includes('.') && row.code.length > 4) ? (
                                <button
                                  onClick={() => handleOpenAhspModal(row)}
                                  className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold transition-all cursor-pointer ${
                                    isHighMapped
                                      ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200'
                                      : isMedMapped
                                      ? 'bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200'
                                      : 'bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200'
                                  }`}
                                  title={`Status Mapping: ${row.ahsp_status || 'Manual'} (Akurasi: ${row.ahsp_score ? (row.ahsp_score * 100).toFixed(0) + '%' : '100%'})`}
                                >
                                  <span className="opacity-75">AHSP:</span>
                                  <span>{row.ahsp_code || row.code}</span>
                                  {row.ahsp_score && (
                                    <span className="text-[10px] font-normal opacity-80">({(row.ahsp_score * 100).toFixed(0)}%)</span>
                                  )}
                                </button>
                              ) : (
                                <button
                                  onClick={() => handleOpenAhspModal(row)}
                                  className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-500 border border-dashed border-slate-300 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 transition-all cursor-pointer"
                                >
                                  + Hubungkan AHSP
                                </button>
                              )}
                            </div>
                            
                            {/* Bottom: Subtitle (only show Hasil AI if item is mapped to standard AHSP) */}
                            {row.ahsp_name && row.ahsp_name !== row.name && (
                              <span className="text-[11.5px] text-slate-500 italic">
                                Hasil AI: {row.name}
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
                              onClick={() => handleOpenEditModal(row)}
                              className="w-6 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center transition-transform hover:scale-105"
                              title="Ubah detail pekerjaan"
                            >
                              <Icons.Edit className="w-3.5 h-3.5" />
                            </button>

                            <div className="relative group/dropdown">
                              <button
                                className="w-9 h-6 rounded-full bg-[#009624] hover:bg-emerald-700 text-white flex items-center justify-center gap-0.5 px-1.5 transition-transform hover:scale-105"
                                title="Opsi lainnya"
                              >
                                <Icons.Grid className="w-3 h-3" />
                                <Icons.ChevronDown className="w-2.5 h-2.5" />
                              </button>

                              <div className="absolute right-0 bottom-full mb-2 w-44 bg-white border border-slate-150 rounded-lg shadow-lg py-1 z-30 hidden group-hover/dropdown:block origin-bottom-right transition-all">
                                <button
                                  onClick={() => handleOpenAhspModal(row)}
                                  className="w-full text-left px-3.5 py-2 hover:bg-emerald-50 flex items-center gap-2 text-[12.5px] text-emerald-800 font-medium"
                                >
                                  <Icons.Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                                  Pilih Kode AHSP
                                </button>
                                <button
                                  onClick={() => handleOpenEditModal(row)}
                                  className="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center gap-2 text-[12.5px] text-slate-700 font-medium"
                                >
                                  <Icons.Edit className="w-3.5 h-3.5 text-slate-600" />
                                  Edit Item
                                </button>
                                <button
                                  onClick={() => handleDeleteItem(row)}
                                  className="w-full text-left px-3.5 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2 text-[12.5px] font-semibold border-t border-slate-50"
                                >
                                  <Icons.Trash className="w-3.5 h-3.5" />
                                  Hapus Item
                                </button>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>

          {/* Footer Controls & Pagination */}
          {totalPages > 0 && (
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100">
              <div className="text-[12.5px] text-slate-500 font-medium">
                Menampilkan <span className="font-semibold text-slate-700">{Math.min(filteredRows.length, (activePage - 1) * pageSize + 1)}</span> - <span className="font-semibold text-slate-700">{Math.min(filteredRows.length, activePage * pageSize)}</span> dari <span className="font-semibold text-slate-700">{filteredRows.length}</span> baris
              </div>

              <div className="flex items-center gap-1">
                <button
                  disabled={activePage === 1}
                  onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                  className="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed text-[12.5px] font-semibold shadow-3xs cursor-pointer transition-colors"
                >
                  Sebelumnya
                </button>

                {paginationRange.map((page, idx) => {
                  if (page === '...') {
                    return (
                      <span key={`dots-${idx}`} className="px-2 text-slate-400 select-none">...</span>
                    );
                  }
                  const isActive = page === activePage;
                  return (
                    <button
                      key={`page-${page}`}
                      onClick={() => setCurrentPage(page)}
                      className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-[12.5px] transition-all cursor-pointer ${isActive
                        ? 'bg-[#009624] text-white shadow-xs'
                        : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300'
                        }`}
                    >
                      {page}
                    </button>
                  );
                })}

                <button
                  disabled={activePage === totalPages}
                  onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                  className="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed text-[12.5px] font-semibold shadow-3xs cursor-pointer transition-colors"
                >
                  Berikutnya
                </button>
              </div>
            </div>
          )}

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

      {/* Modal - AHSP Selection & Search */}
      {showAhspModal && ahspTargetRow && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-2xl w-full overflow-hidden transform scale-100 transition-all">
            <div className="bg-emerald-700 text-white px-5 py-4 flex items-center justify-between">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-wider bg-emerald-800/80 px-2 py-0.5 rounded">
                  Pencarian AHSP Standar (2.816 Master Data)
                </span>
                <h3 className="font-bold text-[14.5px] mt-1">
                  Pilih Kode AHSP untuk: "{ahspTargetRow.name}"
                </h3>
              </div>
              <button
                onClick={() => {
                  setShowAhspModal(false);
                  setAhspTargetRow(null);
                }}
                className="p-1 rounded-full hover:bg-emerald-800 text-emerald-100 hover:text-white transition-colors"
              >
                <Icons.X />
              </button>
            </div>

            <div className="p-5 space-y-4">
              {/* Search Bar */}
              <div>
                <label className="block text-[12px] font-semibold text-slate-600 mb-1">
                  Kata Kunci Pencarian Vector DB:
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={ahspSearchQuery}
                    onChange={(e) => setAhspSearchQuery(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && searchAhspItems(ahspSearchQuery, ahspTargetRow.unit)}
                    placeholder="Masukan nama pekerjaan (contoh: Pengecoran Beton Fc 25)..."
                    className="flex-1 bg-white border border-slate-200 rounded-md py-2 px-3 text-[13px] text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                  />
                  <button
                    onClick={() => searchAhspItems(ahspSearchQuery, ahspTargetRow.unit)}
                    className="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-[12.5px] font-bold transition-colors cursor-pointer"
                  >
                    {isSearchingAhsp ? 'Mencari...' : 'Cari'}
                  </button>
                </div>
              </div>

              {/* AI Candidates List */}
              {ahspTargetRow.ahsp_candidates && ahspTargetRow.ahsp_candidates.length > 0 && (
                <div className="bg-amber-50/70 border border-amber-200 rounded-lg p-3">
                  <span className="text-[11.5px] font-bold text-amber-800 block mb-2">
                    💡 Rekomendasi Kandidat Teratas AI:
                  </span>
                  <div className="space-y-1.5">
                    {ahspTargetRow.ahsp_candidates.map((cand) => (
                      <div
                        key={cand.id_pekerjaan}
                        onClick={() => handleSelectAhspItem(cand)}
                        className="flex items-center justify-between p-2 rounded bg-white border border-amber-150 hover:border-emerald-500 hover:bg-emerald-50/50 cursor-pointer transition-all text-[12.5px]"
                      >
                        <div>
                          <span className="font-bold text-emerald-900 mr-2">{cand.id_pekerjaan}</span>
                          <span className="text-slate-700">{cand.nama_pekerjaan}</span>
                          <span className="text-slate-400 ml-1">({cand.satuan})</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="text-[11px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                            Score: {(cand.score * 100).toFixed(0)}%
                          </span>
                          <button className="text-[11.5px] bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded font-semibold">
                            Pilih
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Search Results List */}
              <div className="border border-slate-200 rounded-lg overflow-hidden max-h-64 overflow-y-auto divide-y divide-slate-100">
                {isSearchingAhsp ? (
                  <div className="py-8 text-center text-slate-400 text-[13px]">
                    Sedang mencari kandidat di Vector DB...
                  </div>
                ) : ahspSearchResults.length === 0 ? (
                  <div className="py-8 text-center text-slate-400 text-[13px]">
                    Tidak ada kode AHSP yang cocok dengan pencarian.
                  </div>
                ) : (
                  ahspSearchResults.map((item) => (
                    <div
                      key={item.id_pekerjaan}
                      onClick={() => handleSelectAhspItem(item)}
                      className="p-3 hover:bg-emerald-50/60 cursor-pointer transition-colors flex items-center justify-between text-[12.5px] group"
                    >
                      <div className="flex-1 pr-4">
                        <div className="flex items-center gap-2">
                          <span className="font-extrabold text-emerald-800 bg-emerald-100/70 px-2 py-0.5 rounded text-[11.5px]">
                            {item.id_pekerjaan}
                          </span>
                          <span className="font-semibold text-slate-800">{item.nama_pekerjaan}</span>
                        </div>
                        <div className="text-[11px] text-slate-400 mt-0.5">
                          Satuan: <span className="font-medium text-slate-600">{item.satuan}</span>
                        </div>
                      </div>
                      <button className="bg-white border border-slate-200 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 px-3 py-1 rounded text-[12px] font-bold transition-all shadow-2xs">
                        Gunakan
                      </button>
                    </div>
                  ))
                )}
              </div>

              <div className="pt-3 border-t border-slate-100 flex justify-end">
                <button
                  onClick={() => {
                    setShowAhspModal(false);
                    setAhspTargetRow(null);
                  }}
                  className="px-4 py-2 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12.5px] font-semibold transition-colors cursor-pointer"
                >
                  Tutup
                </button>
              </div>
            </div>
          </div>
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