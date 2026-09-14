import React, { useState, useMemo, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Navbar from '../components/Navbar';
import { Icons } from '../components/Icons';
import proyekBg from '../assets/proyek-bg13.png';
import {
  updateEstimationItem,
  deleteEstimationItem
} from '../services/api';
import { useProject, DEFAULT_SECTIONS } from '../context/ProjectContext';
import EstimationEmptyState from '../components/anggaran/EstimationEmptyState';

// Currency and numbers formatter helpers (Indonesian format: 1.000,00)
const formatNumber = (value) => {
  return (Number(value) || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const Anggaran = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = useMemo(() => new URLSearchParams(location.search), [location.search]);
  const rawProjectId = queryParams.get('id') || queryParams.get('uuid');
  const projectId = rawProjectId ? rawProjectId.trim() : '';

  const {
    projectDetail,
    setProjectDetail,
    rows,
    setRows,
    updateRow,
    estimationRun,
    setEstimationRun,
    estimationRuns,
    isLoadingWbs,
    loadProjectAndEstimation,
    applyPendingRowUpdate
  } = useProject();

  const [toast, setToast] = useState({ show: false, message: "", type: "success" });

  const triggerToast = (message, type = "success") => {
    setToast({ show: true, message, type });
    setTimeout(() => {
      setToast(prev => ({ ...prev, show: false }));
    }, 3500);
  };

  // Redirect back to projects dashboard if no valid projectId/UUID is provided, or if project not found
  useEffect(() => {
    if (!projectId || projectId === 'undefined' || projectId === 'null') {
      navigate('/', { replace: true });
      return;
    }

    const queryRunId = queryParams.get('run_id') || queryParams.get('run');
    loadProjectAndEstimation(projectId, queryRunId).then(res => {
      if (res && res.notFound) {
        navigate('/', { replace: true });
      }
    });
  }, [projectId, queryParams, loadProjectAndEstimation, navigate]);

  // Keep URL aligned with UUID if available
  useEffect(() => {
    if (projectDetail.uuid && projectId && projectId !== projectDetail.uuid) {
      navigate(`/anggaran?id=${projectDetail.uuid}`, { replace: true });
    }
  }, [projectDetail.uuid, projectId, navigate]);

  // Read session toast triggers and apply any pending row updates (e.g. from Pemetaan AHSP)
  useEffect(() => {
    const toastMsg = sessionStorage.getItem('estimator_toast_msg');
    if (toastMsg) {
      triggerToast(toastMsg, 'success');
      sessionStorage.removeItem('estimator_toast_msg');
    }
    applyPendingRowUpdate();
  }, [applyPendingRowUpdate]);

  // Sections and items structure
  const sections = useMemo(() => {
    const secList = [];
    let currentSec = null;

    rows.forEach(row => {
      if (row.type === 'section') {
        currentSec = {
          ...row,
          items: []
        };
        secList.push(currentSec);
      } else if (currentSec) {
        currentSec.items.push(row);
      }
    });

    return secList;
  }, [rows]);

  // Collapse / Expand state
  // Default to expanded for all sections
  const [collapsedSections, setCollapsedSections] = useState({});
  const isAllCollapsed = useMemo(() => {
    if (sections.length === 0) return false;
    return sections.every(s => (collapsedSections[s.code] !== undefined ? collapsedSections[s.code] : false));
  }, [sections, collapsedSections]);

  const toggleSectionCollapse = (sectionCode) => {
    setCollapsedSections(prev => {
      const current = prev[sectionCode] !== undefined ? prev[sectionCode] : false;
      return {
        ...prev,
        [sectionCode]: !current
      };
    });
  };

  const handleToggleCollapseAll = () => {
    if (isAllCollapsed) {
      // Expand all
      const newMap = {};
      sections.forEach(s => { newMap[s.code] = false; });
      setCollapsedSections(newMap);
    } else {
      // Collapse all
      const newMap = {};
      sections.forEach(s => { newMap[s.code] = true; });
      setCollapsedSections(newMap);
    }
  };

  // Search filter
  const [searchQuery, setSearchQuery] = useState("");

  // Modals state
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showCategoryModal, setShowCategoryModal] = useState(false);
  const [showReorderModal, setShowReorderModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [isReDetecting, setIsReDetecting] = useState(false);

  const [selectedRow, setSelectedRow] = useState(null);
  const [targetSectionCode, setTargetSectionCode] = useState("A");
  const [formData, setFormData] = useState({ name: "", volume: 0, unit: "m2", unitPrice: 0 });

  // New Category form
  const [newCategoryName, setNewCategoryName] = useState("");

  const handleResetData = () => {
    if (window.confirm("Apakah Anda yakin ingin mengembalikan struktur anggaran proyek ini ke default?")) {
      sessionStorage.clear();
      setRows(DEFAULT_SECTIONS);
      setSearchQuery("");
      triggerToast("Data estimasi proyek telah direset ke default.", "success");
    }
  };

  const [showProceedModal, setShowProceedModal] = useState(false);

  // Compute all items and unmapped items for alert / confirmation modal
  const allItemRows = useMemo(() => rows.filter(r => r.type === 'item'), [rows]);
  const unmappedItems = useMemo(() => allItemRows.filter(r => r.ahsp_status === 'unmapped'), [allItemRows]);

  const handleOpenAhspModal = (row) => {
    const activeProjectIdentifier = projectDetail.uuid || projectId;
    navigate(`/pemetaan-ahsp?id=${activeProjectIdentifier}`, {
      state: {
        projectId: activeProjectIdentifier,
        targetRow: row,
        returnUrl: `/anggaran?id=${activeProjectIdentifier}`
      }
    });
  };

  const handleSaveEstimation = () => {
    setShowProceedModal(true);
  };

  const proceedToRab = () => {
    const activeProjectIdentifier = projectDetail.uuid || projectId;
    navigate(`/rab?id=${activeProjectIdentifier}`);
  };

  // Add Item
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
      volume: Number(formData.volume) || 0,
      unit: formData.unit || 'm2',
      unitPrice: Number(formData.unitPrice) || 0,
      confidence: 'high',
      ahsp_status: 'manual',
      ahsp_candidates: []
    };

    const newRows = [...rows];
    newRows.splice(insertIdx, 0, newItem);

    // Make sure section is expanded so user sees the newly added item
    setCollapsedSections(prev => ({ ...prev, [targetSectionCode]: false }));

    setRows(newRows);
    setEstimationRun(prev => prev ? {
      ...prev,
      summary_metrics: {
        ...prev.summary_metrics,
        total_items: (prev.summary_metrics?.total_items ?? rows.filter(r => r.type === 'item').length) + 1
      }
    } : null);
    setShowAddModal(false);
    setFormData({ name: "", volume: 0, unit: "", unitPrice: 0 });
    triggerToast(`Berhasil menambahkan "${formData.name}" ke ${targetSectionCode}!`);
  };

  // Edit Item
  const handleEditItem = async (e) => {
    e.preventDefault();
    if (!selectedRow) return;

    const updatedVolume = Number(formData.volume);
    const updatedUnitPrice = Number(formData.unitPrice);

    if (selectedRow.db_id) {
      try {
        await updateEstimationItem(selectedRow.db_id, {
          volume: updatedVolume,
          unit_price: updatedUnitPrice,
          name: formData.name,
          unit: formData.unit
        });
      } catch (err) {
        console.warn("Could not update item in backend database:", err.message);
      }
    }

    const newRows = rows.map(r => {
      if (r.id === selectedRow.id) {
        return {
          ...r,
          name: formData.name,
          volume: updatedVolume,
          unit: formData.unit,
          unitPrice: updatedUnitPrice
        };
      }
      return r;
    });

    setRows(newRows);
    setShowEditModal(false);
    setSelectedRow(null);
    triggerToast(`Detail pekerjaan "${formData.name}" berhasil diperbarui.`);
  };

  // Delete Item
  const handleDeleteItem = async (item) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus "${item.name}"?`)) {
      if (item.db_id) {
        try {
          await deleteEstimationItem(item.db_id);
        } catch (err) {
          console.warn("Could not delete item in backend database:", err.message);
        }
      }

      const newRows = rows.filter(r => r.id !== item.id);
      setRows(newRows);
      setEstimationRun(prev => prev ? {
        ...prev,
        summary_metrics: {
          ...prev.summary_metrics,
          total_items: Math.max(0, (prev.summary_metrics?.total_items ?? rows.filter(r => r.type === 'item').length) - 1)
        }
      } : null);
      triggerToast(`Pekerjaan "${item.name}" berhasil dihapus.`, "warning");
    }
  };

  // Delete Section
  const handleDeleteSection = (sec) => {
    if (window.confirm(`Apakah Anda yakin ingin menghapus kategori "${sec.name}" beserta seluruh item di dalamnya?`)) {
      const itemsInSec = rows.filter(r => r.type === 'item' && r.sectionCode === sec.code).length;
      const newRows = rows.filter(r => {
        if (r.id === sec.id) return false;
        if (r.type === 'item' && r.sectionCode === sec.code) return false;
        return true;
      });
      setRows(newRows);
      setEstimationRun(prev => prev ? {
        ...prev,
        summary_metrics: {
          ...prev.summary_metrics,
          total_items: Math.max(0, (prev.summary_metrics?.total_items ?? rows.filter(r => r.type === 'item').length) - itemsInSec)
        }
      } : null);
      triggerToast(`Kategori "${sec.name}" berhasil dihapus.`, "warning");
    }
  };

  // Add Category
  const handleAddCategory = (e) => {
    e.preventDefault();
    if (!newCategoryName.trim()) return;

    const existingCodes = rows.filter(r => r.type === 'section').map(r => r.code);
    let nextCharCode = 65; // 'A'
    while (existingCodes.includes(String.fromCharCode(nextCharCode))) {
      nextCharCode++;
    }
    const newCode = String.fromCharCode(nextCharCode);

    const newSec = {
      id: `sec-${newCode}-${Date.now()}`,
      type: 'section',
      code: newCode,
      name: newCategoryName.trim().toUpperCase()
    };

    setRows([...rows, newSec]);
    setNewCategoryName("");
    triggerToast(`Kategori "${newSec.name}" berhasil ditambahkan.`);
  };

  // Update Category Name
  const handleUpdateCategoryName = (secId, updatedName) => {
    if (!updatedName.trim()) return;
    setRows(rows.map(r => r.id === secId ? { ...r, name: updatedName.trim().toUpperCase() } : r));
    triggerToast("Nama kategori berhasil diubah.");
  };

  // Move Section Up / Down
  const handleMoveSection = (secCode, direction) => {
    const secIdxList = [];
    rows.forEach((r, idx) => {
      if (r.type === 'section') secIdxList.push({ code: r.code, index: idx });
    });

    const currentPos = secIdxList.findIndex(s => s.code === secCode);
    if (currentPos === -1) return;
    if (direction === 'up' && currentPos === 0) return;
    if (direction === 'down' && currentPos === secIdxList.length - 1) return;

    const targetPos = direction === 'up' ? currentPos - 1 : currentPos + 1;
    const targetCode = secIdxList[targetPos].code;

    // Extract blocks
    const grouped = {};
    let activeSec = null;
    rows.forEach(r => {
      if (r.type === 'section') {
        activeSec = r.code;
        grouped[activeSec] = [r];
      } else if (activeSec) {
        grouped[activeSec].push(r);
      }
    });

    const orderedCodes = secIdxList.map(s => s.code);
    const temp = orderedCodes[currentPos];
    orderedCodes[currentPos] = orderedCodes[targetPos];
    orderedCodes[targetPos] = temp;

    const reorderedRows = [];
    orderedCodes.forEach(c => {
      if (grouped[c]) reorderedRows.push(...grouped[c]);
    });

    setRows(reorderedRows);
    triggerToast(`Urutan kategori berhasil dipindahkan.`);
  };

  // Filtered Sections and Items based on Search
  const filteredSections = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();
    if (!query) return sections;

    return sections
      .map(sec => {
        const secNameMatch = sec.name.toLowerCase().includes(query);
        const matchingItems = sec.items.filter(item => {
          return (
            (item.name || '').toLowerCase().includes(query) ||
            (item.ahsp_code || item.code || '').toLowerCase().includes(query) ||
            (item.ahsp_name || '').toLowerCase().includes(query)
          );
        });

        if (secNameMatch || matchingItems.length > 0) {
          return {
            ...sec,
            items: secNameMatch ? sec.items : matchingItems,
            matchesQuery: true
          };
        }
        return null;
      })
      .filter(Boolean);
  }, [sections, searchQuery]);

  // Total Items count: pulled from fetchEstimationRun (summary_metrics.total_items) with fallback to rows count
  const totalItemsCount = useMemo(() => {
    if (estimationRun?.summary_metrics?.total_items !== undefined) {
      return estimationRun.summary_metrics.total_items;
    }
    if (estimationRun?.total_items !== undefined) {
      return estimationRun.total_items;
    }
    return rows.filter(r => r.type === 'item').length;
  }, [estimationRun, rows]);

  // Pengecekan apakah proyek belum memiliki riwayat/data estimasi (estimation_runs kosong)
  const hasNoEstimationRuns = useMemo(() => {
    if (isLoadingWbs) return false;
    const runs = projectDetail?.estimation_runs || estimationRuns;
    const hasZeroRuns = Array.isArray(runs) && runs.length === 0;
    const hasNoRunData = !estimationRun || !estimationRun.sections || estimationRun.sections.length === 0;
    return hasZeroRuns || (hasNoRunData && totalItemsCount === 0);
  }, [isLoadingWbs, projectDetail?.estimation_runs, estimationRuns, estimationRun, totalItemsCount]);

  const handleEstimationSuccess = async () => {
    setIsReDetecting(false);
    await loadProjectAndEstimation(projectId, null, true);
  };

  // If no projectId provided, do not render contents while redirecting
  if (!projectId || projectId === 'undefined' || projectId === 'null') {
    return null;
  }

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      {/* Top Navigation */}
      <Navbar onResetData={handleResetData} onNavigateToRab={handleSaveEstimation} />

      {/* Hero Banner with Green Wavy Pattern and Project Title */}
      <div className="w-full relative overflow-hidden h-10 md:h-28 flex items-center justify-center select-none bg-[#84c225] shadow-xs">
        {/* Background Graphic Asset */}
        <img
          src={proyekBg}
          alt="Banner Estimator"
          className="absolute inset-0 w-full h-full object-center pointer-events-none"
        />

        {/* Centered Project Name */}
        <div className="relative z-10 text-center px-4 max-w-2xl">
          <h1 className="text-xl md:text-2xl lg:text-3xl font-semibold text-white tracking-wider uppercase drop-shadow-sm font-sans">
            {projectDetail.title || ''}
          </h1>
        </div>
      </div>

      {/* Main Content Workspace Card */}
      <main className="max-w-[1360px] mx-auto px-4 mt-6">
        {isLoadingWbs ? (
          <div className="bg-white rounded-xl shadow-xs border border-slate-200/80 p-12 text-center text-slate-500 font-medium">
            <div className="flex flex-col items-center justify-center gap-3">
              <div className="w-8 h-8 border-3 border-[#0fa83c] border-t-transparent rounded-full animate-spin"></div>
              <span className="text-sm font-semibold text-slate-700">Memuat data estimasi anggaran...</span>
            </div>
          </div>
        ) : (hasNoEstimationRuns || isReDetecting) ? (
          <EstimationEmptyState
            projectId={projectId}
            projectDetail={projectDetail}
            onEstimationSuccess={handleEstimationSuccess}
            triggerToast={triggerToast}
            onCancel={!hasNoEstimationRuns && isReDetecting ? () => setIsReDetecting(false) : null}
          />
        ) : (
          <div className="bg-white rounded-xl shadow-xs border border-slate-200/80 p-5 md:p-6">
            {/* Action Toolbar: Search & Action Buttons */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-150">
              {/* Search and item count */}
              <div className="flex items-center gap-3 flex-1 max-w-md">
                <div className="relative w-full">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Cari pekerjaan, seksi, atau kode AHSP..."
                    className="w-full pl-9 pr-7 py-1.5 text-xs rounded-lg focus:outline-none transition-all bg-slate-50 border border-slate-250 focus:border-[#0fa83c] focus:bg-white text-slate-700 placeholder-slate-400"
                  />
                  <svg className="w-4 h-4 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                  </svg>
                  {searchQuery && (
                    <button
                      type="button"
                      onClick={() => setSearchQuery("")}
                      className="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 text-sm font-bold cursor-pointer"
                      title="Hapus pencarian"
                    >
                      ×
                    </button>
                  )}
                </div>
              </div>
            </div>

            {/* Table Container */}
            <div className="overflow-x-auto border border-slate-200/90 rounded-sm shadow-3xs max-h-screen">
              <table className="w-full border-collapse text-left text-[12.5px]">
                {/* Header: Solid Dark Green */}
                <thead className="bg-[#089613] text-white select-none sticky top-0 z-10">
                  <tr>
                    <th scope="col" className="py-2.5 px-3 text-left w-12 font-bold text-[12px] tracking-wide">
                      No.
                    </th>
                    <th scope="col" className="py-2.5 px-4 text-left font-bold text-[12px] tracking-wide">
                      Uraian Pekerjaan
                    </th>
                    <th scope="col" className="py-2.5 px-3 text-center w-24 font-bold text-[12px] tracking-wide">
                      Volume
                    </th>
                    <th scope="col" className="py-2.5 px-3 text-center w-20 font-bold text-[12px] tracking-wide">
                      Satuan
                    </th>
                    <th scope="col" className="py-2.5 px-3 text-center w-24 font-bold text-[12px] tracking-wide">
                      Aksi
                    </th>
                  </tr>
                </thead>

                {/* Table Body */}
                <tbody className="divide-y divide-slate-100">
                  {filteredSections.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="py-12 text-center text-slate-400 font-medium">
                        {searchQuery ? `Tidak ada pekerjaan yang cocok dengan pencarian "${searchQuery}".` : 'Tidak ada data pekerjaan.'}
                      </td>
                    </tr>
                  ) : (
                    filteredSections.map((sec, secIdx) => {

                    const isCollapsed = collapsedSections[sec.code] !== undefined ? collapsedSections[sec.code] : false; // default expanded

                    return (
                      <React.Fragment key={sec.id}>
                        {/* Category / Section Header Row */}
                        <tr className="bg-white hover:bg-[#f4faf4] transition-colors group">
                          {/* Col 1: Red Minus/Plus Circle Button */}
                          <td className="py-2 px-3 text-center select-none align-middle">
                            <button
                              type="button"
                              onClick={() => toggleSectionCollapse(sec.code)}
                              className="w-4 h-4 rounded-full bg-[#d32f2f] hover:bg-red-700 flex items-center justify-center text-white cursor-pointer shadow-3xs transition-transform active:scale-95"
                              title={isCollapsed ? "Buka rincian pekerjaan kategori ini" : "Tutup rincian pekerjaan kategori ini"}
                            >
                              {isCollapsed ? (
                                <div className="relative w-2 h-2 flex items-center justify-center">
                                  <div className="absolute w-2 h-0.5 bg-white rounded-full"></div>
                                  <div className="absolute w-0.5 h-2 bg-white rounded-full"></div>
                                </div>
                              ) : (
                                <div className="w-2 h-0.5 bg-white rounded-full"></div>
                              )}
                            </button>
                          </td>

                          {/* Col 2: Category Title */}
                          <td
                            onClick={() => toggleSectionCollapse(sec.code)}
                            className="py-2.5 px-4 font-bold text-slate-800 uppercase text-[12.5px] tracking-wide align-middle cursor-pointer select-none"
                            title={isCollapsed ? "Buka rincian pekerjaan kategori ini" : "Tutup rincian pekerjaan kategori ini"}
                          >
                            {sec.name}
                          </td>

                          {/* Col 3: Volume (Blank) */}
                          <td className="py-2.5 px-3 text-center text-slate-400 text-[12px] align-middle"></td>

                          {/* Col 4: Satuan (Blank) */}
                          <td className="py-2.5 px-3 text-center text-slate-400 text-[12px] align-middle"></td>

                          {/* Col 4: Satuan (Blank) */}
                          <td className="py-2.5 px-3 text-center text-slate-400 text-[12px] align-middle"></td>
                        </tr>

                        {/* Items Rows in this Section */}
                        {!isCollapsed && sec.items.map((item, itemIdx) => {
                          const itemVolume = Number(item.volume) || 0;

                          return (
                            <tr
                              key={item.id || itemIdx}
                              className="bg-[#fafcfa] hover:bg-[#edf7ee] transition-colors"
                            >
                              {/* Col 1: Empty or Sub-number */}
                              <td className="py-2.5 px-3 text-center text-slate-400 text-[11px] align-middle">
                                {item.no || itemIdx + 1}
                              </td>

                              {/* Col 2: Uraian Pekerjaan + Status Badge */}
                              <td className="py-2.5 px-4 text-slate-800 align-middle">
                                <div className="flex gap-2">
                                  <span className="font-semibold text-slate-800 text-[12px]">
                                    {item.ahsp_name || item.name || ''}
                                  </span>
                                  {item.ahsp_status == "unmapped" && (
                                    <span className="text-xs font-medium text-red-800 bg-red-100 px-1.5 py-0.2 rounded border border-red-400 self-start" title="AHSP belum dipetakan">
                                      !
                                    </span>
                                  )}
                                </div>
                              </td>

                              {/* Col 3: Volume */}
                              <td className="py-2.5 px-3 text-center text-slate-700 font-medium text-[12px] tabular-nums">
                                {formatNumber(itemVolume)}
                              </td>

                              {/* Col 4: Satuan */}
                              <td className="py-2.5 px-3 text-center text-slate-600 text-[12px]">
                                {item.ahsp_unit || item.unit || ''}
                              </td>

                              {/* Col 5: Aksi (Edit, Pemetaan AHSP, Hapus) */}
                              <td className="py-2.5 px-3 text-center">
                                <div className="inline-flex items-center justify-center gap-1">
                                  {/* Pemetaan AHSP button */}
                                  <button
                                    type="button"
                                    onClick={() => handleOpenAhspModal(item)}
                                    className="p-1 rounded text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50 transition-colors cursor-pointer"
                                    title="Pemetaan AHSP"
                                  >
                                    <Icons.Book className="w-3.5 h-3.5" />
                                  </button>

                                  {/* Delete item button */}
                                  <button
                                    type="button"
                                    onClick={() => handleDeleteItem(item)}
                                    className="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors cursor-pointer"
                                    title="Hapus Item"
                                  >
                                    <Icons.Trash className="w-3.5 h-3.5" />
                                  </button>
                                </div>
                              </td>
                            </tr>
                          );
                        })}
                      </React.Fragment>
                    );
                  })
                  )}
                </tbody>

                {/* Table Footer: Total Items */}
                <tfoot className="bg-[#00802b] text-white font-semibold text-[13px] select-none border-t border-[#006e24]/40 sticky bottom-0">
                  {/* 3. TOTAL ITEMS */}
                  <tr className="border-t border-[#006e24]/40">
                    <td colSpan={4} className="py-2.5 px-4 text-right tracking-wide">
                      Total Item
                    </td>
                    <td className="py-2.5 px-4 text-right tracking-wider tabular-nums font-bold">
                      {totalItemsCount}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div className="p-4 flex justify-end">
              <button
                onClick={() => handleSaveEstimation()}
                className="px-6 py-2 text-white bg-[#00802b] hover:bg-[#006e24] cursor-pointer active:scale-98 transition-colors rounded-full font-bold text-[13.5px] tracking-wide shadow-md"
              >
                Lanjut ke RAB
              </button>
            </div>
          </div>
        )}
      </main>

      {/* Floating Toast Notification */}
      {toast.show && (
        <div className={`fixed bottom-5 right-5 z-50 flex items-center gap-2.5 px-4 py-3 rounded-lg shadow-xl text-white font-medium transition-all transform animate-bounce duration-300 ${toast.type === 'success'
          ? 'bg-[#00802b] border border-emerald-500'
          : toast.type === 'warning'
            ? 'bg-amber-600 border border-amber-500'
            : 'bg-red-600 border border-red-500'
          }`}>
          <Icons.Info className="w-4 h-4" />
          <span className="text-[12.5px]">{toast.message}</span>
        </div>
      )}

      {/* Modal: Konfirmasi Lanjut ke RAB */}
      {showProceedModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden transform scale-100 transition-all">

            {/* KASUS 1: Masih ada item yang belum dipetakan (UNMAPPED) */}
            {unmappedItems.length > 0 ? (
              <>
                {/* Header Warning Amber */}
                <div className="bg-amber-600 text-white px-5 py-3.5 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Icons.AlertTriangle className="w-5 h-5 text-amber-100" />
                    <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                      Perhatian: Item Belum Terpetakan
                    </h3>
                  </div>
                  <button
                    onClick={() => setShowProceedModal(false)}
                    className="p-1 rounded-full hover:bg-amber-700 text-white transition-colors cursor-pointer"
                  >
                    <Icons.X className="w-4 h-4" />
                  </button>
                </div>

                <div className="p-5 space-y-4">
                  {/* Alert banner */}
                  <div className="bg-amber-50 border border-amber-200/80 rounded-xl p-3.5 flex gap-3 text-amber-900">
                    <div className="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-bold text-sm">
                      !
                    </div>
                    <div className="text-[12.5px] leading-relaxed">
                      <p className="font-bold text-amber-950">
                        Ada {unmappedItems.length} pekerjaan yang belum dipetakan ke AHSP
                      </p>
                      <p className="text-amber-800 text-[12px] mt-0.5">
                        Item yang belum dipetakan tidak memiliki kode dan acuan harga satuan standar. Disarankan untuk memetakan seluruh pekerjaan terlebih dahulu.
                      </p>
                    </div>
                  </div>

                  {/* List of unmapped items */}
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-[11.5px] font-bold text-slate-600 uppercase tracking-wider">
                        Daftar Pekerjaan Belum Dipetakan ({unmappedItems.length}):
                      </span>
                      <span className="text-[11px] text-slate-400">
                        Klik tombol untuk memetakan
                      </span>
                    </div>

                    <div className="max-h-52 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100 bg-slate-50/50 pr-1">
                      {unmappedItems.map((item, idx) => (
                        <div key={item.id || idx} className="p-2.5 flex items-center justify-between gap-3 hover:bg-white transition-colors">
                          <div className="flex items-start gap-2.5 min-w-0 flex-1">
                            <span className="w-5 h-5 rounded-full bg-red-100 text-red-700 font-bold text-[10.5px] flex items-center justify-center shrink-0 mt-0.5">
                              !
                            </span>
                            <div className="min-w-0 flex-1">
                              <p className="text-[12px] font-semibold text-slate-800 truncate" title={item.name}>
                                {item.name}
                              </p>
                              <div className="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                                <span className="bg-slate-200/80 px-1.5 py-0.2 rounded text-[10px] font-medium text-slate-700">
                                  Kategori {item.sectionCode || '-'}
                                </span>
                                <span>•</span>
                                <span>Vol: {formatNumber(item.volume)} {item.unit}</span>
                              </div>
                            </div>
                          </div>

                          <button
                            type="button"
                            onClick={() => {
                              setShowProceedModal(false);
                              handleOpenAhspModal(item);
                            }}
                            className="px-2.5 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded-lg transition-colors cursor-pointer shrink-0 flex items-center gap-1 shadow-2xs"
                            title="Petakan pekerjaan ini sekarang"
                          >
                            <Icons.Book className="w-3 h-3" />
                            Petakan
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Confirmation prompt */}
                  <div className="bg-slate-50 rounded-lg p-3 text-center border border-slate-200/70">
                    <p className="text-[12.5px] font-semibold text-slate-700">
                      Apakah Anda yakin ingin tetap melanjutkan ke halaman RAB?
                    </p>
                  </div>

                  {/* Action buttons */}
                  <div className="pt-2 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button
                      type="button"
                      onClick={() => setShowProceedModal(false)}
                      className="px-4 py-2 border border-slate-200 rounded-lg text-slate-700 hover:bg-slate-50 text-[12px] font-semibold transition-colors cursor-pointer"
                    >
                      Periksa & Petakan Dulu
                    </button>

                    <button
                      type="button"
                      onClick={() => {
                        setShowProceedModal(false);
                        proceedToRab();
                      }}
                      className="px-4.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[12px] font-bold shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
                    >
                      <span>Tetap Lanjut ke RAB</span>
                      <Icons.ChevronRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </>
            ) : allItemRows.length > 0 ? (
              /* KASUS 2: Seluruh item sudah terpetakan (SIAP) */
              <>
                <div className="bg-[#00802b] text-white px-5 py-3.5 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Icons.CheckCircle className="w-5 h-5 text-emerald-200" />
                    <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                      Siap Lanjut ke RAB
                    </h3>
                  </div>
                  <button
                    onClick={() => setShowProceedModal(false)}
                    className="p-1 rounded-full hover:bg-emerald-800 text-white transition-colors cursor-pointer"
                  >
                    <Icons.X className="w-4 h-4" />
                  </button>
                </div>

                <div className="p-5 space-y-4">
                  <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex gap-3.5 text-emerald-900">
                    <div className="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold text-base">
                      ✓
                    </div>
                    <div className="text-[12.5px] leading-relaxed">
                      <p className="font-bold text-emerald-950">
                        Seluruh Pekerjaan Telah Terpetakan ({allItemRows.length} item)
                      </p>
                      <p className="text-emerald-800 text-[12px] mt-0.5">
                        Semua item pekerjaan telah berhasil dipetakan ke standar AHSP. Data siap disusun menjadi Rencana Anggaran Biaya (RAB).
                      </p>
                    </div>
                  </div>

                  <p className="text-[12.5px] font-semibold text-slate-700 text-center py-1">
                    Lanjutkan ke halaman penyusunan Rencana Anggaran Biaya (RAB)?
                  </p>

                  <div className="pt-2 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button
                      type="button"
                      onClick={() => setShowProceedModal(false)}
                      className="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-[12px] font-semibold transition-colors cursor-pointer"
                    >
                      Batal
                    </button>

                    <button
                      type="button"
                      onClick={() => {
                        setShowProceedModal(false);
                        proceedToRab();
                      }}
                      className="px-5 py-2 bg-[#00802b] hover:bg-emerald-700 text-white rounded-lg text-[12.5px] font-bold shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
                    >
                      <span>Lanjut ke RAB</span>
                      <Icons.ChevronRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </>
            ) : (
              /* KASUS 3: Tidak ada item sama sekali */
              <>
                <div className="bg-slate-700 text-white px-5 py-3.5 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Icons.Info className="w-5 h-5 text-slate-200" />
                    <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                      Data Pekerjaan Kosong
                    </h3>
                  </div>
                  <button
                    onClick={() => setShowProceedModal(false)}
                    className="p-1 rounded-full hover:bg-slate-800 text-white transition-colors cursor-pointer"
                  >
                    <Icons.X className="w-4 h-4" />
                  </button>
                </div>

                <div className="p-5 space-y-4">
                  <p className="text-[12.5px] text-slate-600 leading-relaxed">
                    Belum ada item pekerjaan yang terdaftar pada proyek ini. Silakan tambahkan pekerjaan terlebih dahulu sebelum menyusun RAB.
                  </p>

                  <div className="pt-2 border-t border-slate-100 flex justify-end">
                    <button
                      type="button"
                      onClick={() => setShowProceedModal(false)}
                      className="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-[12px] font-semibold transition-colors cursor-pointer"
                    >
                      Tutup
                    </button>
                  </div>
                </div>
              </>
            )}

          </div>
        </div>
      )}

      {/* Modal: Tambah Item Pekerjaan */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden transform scale-100 transition-all">
            <div className="bg-[#00802b] text-white px-5 py-3.5 flex items-center justify-between">
              <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                Tambah Pekerjaan (Bagian {targetSectionCode})
              </h3>
              <button
                onClick={() => setShowAddModal(false)}
                className="p-1 rounded-full hover:bg-emerald-800 text-white transition-colors cursor-pointer"
              >
                <Icons.X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleAddItem} className="p-5 space-y-4">
              <div>
                <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                  Uraian Pekerjaan <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Misal: Pemasangan keramik lantai..."
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                    Volume <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    required
                    step="0.01"
                    min="0"
                    value={formData.volume}
                    onChange={(e) => setFormData({ ...formData, volume: Number(e.target.value) })}
                    className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="m2, m3, unit, ls..."
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                  Harga Satuan (Rp)
                </label>
                <input
                  type="number"
                  step="100"
                  min="0"
                  placeholder="0"
                  value={formData.unitPrice}
                  onChange={(e) => setFormData({ ...formData, unitPrice: Number(e.target.value) })}
                  className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                />
              </div>

              <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-1.5 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12px] font-semibold transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-[#00802b] hover:bg-emerald-700 text-white rounded-md text-[12px] font-semibold shadow-xs transition-colors cursor-pointer"
                >
                  Simpan Pekerjaan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Ubah Item Pekerjaan */}
      {showEditModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden transform scale-100 transition-all">
            <div className="bg-[#00802b] text-white px-5 py-3.5 flex items-center justify-between">
              <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                Ubah Detail Pekerjaan
              </h3>
              <button
                onClick={() => {
                  setShowEditModal(false);
                  setSelectedRow(null);
                }}
                className="p-1 rounded-full hover:bg-emerald-800 text-white transition-colors cursor-pointer"
              >
                <Icons.X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleEditItem} className="p-5 space-y-4">
              <div>
                <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                  Uraian Pekerjaan <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                    Volume <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    required
                    step="0.01"
                    min="0"
                    value={formData.volume}
                    onChange={(e) => setFormData({ ...formData, volume: Number(e.target.value) })}
                    className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.unit}
                    onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                    className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-[12px] font-semibold text-slate-700 mb-1">
                  Harga Satuan (Rp)
                </label>
                <input
                  type="number"
                  step="100"
                  min="0"
                  value={formData.unitPrice}
                  onChange={(e) => setFormData({ ...formData, unitPrice: Number(e.target.value) })}
                  className="w-full bg-white border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b] transition-colors"
                />
              </div>

              <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => {
                    setShowEditModal(false);
                    setSelectedRow(null);
                  }}
                  className="px-4 py-1.5 border border-slate-200 rounded-md text-slate-700 hover:bg-slate-50 text-[12px] font-semibold transition-colors cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-[#00802b] hover:bg-emerald-700 text-white rounded-md text-[12px] font-semibold shadow-xs transition-colors cursor-pointer"
                >
                  Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Ubah Kategori */}
      {showCategoryModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden">
            <div className="bg-[#00802b] text-white px-5 py-3.5 flex items-center justify-between">
              <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                Kelola Kategori Pekerjaan
              </h3>
              <button
                onClick={() => setShowCategoryModal(false)}
                className="p-1 rounded-full hover:bg-emerald-800 text-white transition-colors cursor-pointer"
              >
                <Icons.X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-5 space-y-4">
              {/* Add new category */}
              <form onSubmit={handleAddCategory} className="flex items-center gap-2">
                <input
                  type="text"
                  placeholder="Nama kategori baru (contoh: PEKERJAAN TAMAN)..."
                  value={newCategoryName}
                  onChange={(e) => setNewCategoryName(e.target.value)}
                  className="flex-1 border border-slate-300 rounded-md py-2 px-3 text-[12.5px] text-slate-700 focus:outline-none focus:border-[#00802b] focus:ring-1 focus:ring-[#00802b]"
                />
                <button
                  type="submit"
                  className="px-4 py-2 bg-[#7cb342] hover:bg-[#689f38] text-white text-[12px] font-bold rounded-md shadow-xs transition-colors cursor-pointer whitespace-nowrap"
                >
                  + Tambah
                </button>
              </form>

              {/* List of existing categories */}
              <div className="space-y-2 max-h-64 overflow-y-auto pr-1">
                <span className="text-[11.5px] font-bold text-slate-500 uppercase tracking-wider block">
                  Daftar Kategori Saat Ini ({sections.length}):
                </span>
                {sections.map((sec) => (
                  <div key={sec.id} className="flex items-center justify-between gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    <div className="flex items-center gap-2 flex-1">
                      <span className="w-6 h-6 rounded bg-emerald-100 text-emerald-800 font-bold text-[11px] flex items-center justify-center">
                        {sec.code}
                      </span>
                      <input
                        type="text"
                        defaultValue={sec.name}
                        onBlur={(e) => handleUpdateCategoryName(sec.id, e.target.value)}
                        className="flex-1 bg-white border border-slate-200 rounded px-2.5 py-1 text-[12px] text-slate-800 font-bold focus:outline-none focus:border-[#00802b]"
                      />
                    </div>
                    <button
                      type="button"
                      onClick={() => handleDeleteSection(sec)}
                      className="p-1.5 text-slate-400 hover:text-red-600 transition-colors cursor-pointer"
                      title="Hapus Kategori"
                    >
                      <Icons.Trash className="w-4 h-4" />
                    </button>
                  </div>
                ))}
              </div>

              <div className="pt-3 border-t border-slate-100 flex justify-end">
                <button
                  type="button"
                  onClick={() => setShowCategoryModal(false)}
                  className="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-[12px] font-semibold transition-colors cursor-pointer"
                >
                  Selesai
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Atur Urutan Uraian */}
      {showReorderModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden">
            <div className="bg-[#00802b] text-white px-5 py-3.5 flex items-center justify-between">
              <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                Atur Urutan Kategori Uraian
              </h3>
              <button
                onClick={() => setShowReorderModal(false)}
                className="p-1 rounded-full hover:bg-emerald-800 text-white transition-colors cursor-pointer"
              >
                <Icons.X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-5 space-y-3">
              <p className="text-[12px] text-slate-500">
                Gunakan tombol panah di bawah untuk memindahkan urutan kategori pekerjaan:
              </p>

              <div className="space-y-2 max-h-72 overflow-y-auto pr-1">
                {sections.map((sec, idx) => (
                  <div key={sec.id} className="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <div className="flex items-center gap-2.5">
                      <span className="w-6 h-6 rounded bg-[#7cb342] text-white font-bold text-[11px] flex items-center justify-center">
                        {idx + 1}
                      </span>
                      <span className="font-bold text-[12.5px] text-slate-800">
                        {sec.name}
                      </span>
                      <span className="text-[11px] text-slate-400 font-medium">
                        ({sec.items.length} pekerjaan)
                      </span>
                    </div>

                    <div className="flex items-center gap-1">
                      <button
                        type="button"
                        disabled={idx === 0}
                        onClick={() => handleMoveSection(sec.code, 'up')}
                        className={`p-1 rounded ${idx === 0 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200 cursor-pointer'}`}
                        title="Geser ke Atas"
                      >
                        ▲
                      </button>
                      <button
                        type="button"
                        disabled={idx === sections.length - 1}
                        onClick={() => handleMoveSection(sec.code, 'down')}
                        className={`p-1 rounded ${idx === sections.length - 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-200 cursor-pointer'}`}
                        title="Geser ke Bawah"
                      >
                        ▼
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              <div className="pt-3 border-t border-slate-100 flex justify-end">
                <button
                  type="button"
                  onClick={() => setShowReorderModal(false)}
                  className="px-4 py-1.5 bg-[#00802b] hover:bg-emerald-700 text-white rounded-md text-[12px] font-semibold transition-colors cursor-pointer"
                >
                  Selesai
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Impor Volume */}
      {showImportModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto animate-fade-in">
          <div className="bg-white rounded-xl shadow-2xl border border-slate-100 max-w-md w-full overflow-hidden">
            <div className="bg-[#00802b] text-white px-5 py-3.5 flex items-center justify-between">
              <h3 className="font-bold text-[13.5px] uppercase tracking-wide">
                Impor Volume Pekerjaan
              </h3>
              <button
                onClick={() => setShowImportModal(false)}
                className="p-1 rounded-full hover:bg-emerald-800 text-white transition-colors cursor-pointer"
              >
                <Icons.X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-5 space-y-4">
              <p className="text-[12px] text-slate-600 leading-relaxed">
                Pilih file CSV atau Excel berisi daftar uraian pekerjaan dan volume untuk dimasukkan ke dalam estimasi proyek ini.
              </p>

              <div className="border-2 border-dashed border-slate-250 rounded-xl p-6 text-center hover:border-emerald-500 transition-colors bg-slate-50/50">
                <Icons.Grid className="w-8 h-8 text-emerald-600 mx-auto mb-2 opacity-80" />
                <label className="cursor-pointer block">
                  <span className="text-[12.5px] font-bold text-emerald-800 hover:underline">
                    Pilih Berkas CSV / Excel
                  </span>
                  <input
                    type="file"
                    accept=".csv,.xlsx,.xls"
                    className="hidden"
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (file) {
                        triggerToast(`File "${file.name}" siap diproses!`);
                        setShowImportModal(false);
                      }
                    }}
                  />
                </label>
                <span className="text-[11px] text-slate-400 block mt-1">Format didukung: .csv, .xlsx, .xls</span>
              </div>

              <div className="pt-2 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowImportModal(false)}
                  className="px-4 py-1.5 border border-slate-200 rounded-md text-slate-600 hover:bg-slate-50 text-[12px] font-semibold transition-colors cursor-pointer"
                >
                  Tutup
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Anggaran;