import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Navbar from '../components/Navbar';
import { Icons } from '../components/Icons';
import proyekBg from '../assets/proyek-bg13.png';
import { updateEstimationItem } from '../services/api';
import { useProject } from '../context/ProjectContext';

const PemetaanAhsp = () => {
  const { updateRow } = useProject();
  const navigate = useNavigate();
  const location = useLocation();
  const tableContainerRef = useRef(null);

  // Extract target row & project context from state or URL query
  const locationState = location.state || {};
  const queryParams = useMemo(() => new URLSearchParams(location.search), [location.search]);
  const [projectId, setProjectId] = useState(() => {
    const raw = (
      queryParams.get('id') ||
      queryParams.get('uuid') ||
      queryParams.get('project') ||
      locationState.projectId ||
      sessionStorage.getItem('estimator_ahsp_project_id') ||
      ''
    );
    return raw ? raw.trim() : '';
  });
  const [targetRow, setTargetRow] = useState(locationState.targetRow || null);

  // Redirect back to projects dashboard if no valid projectId/UUID is provided
  useEffect(() => {
    if (!projectId || projectId === 'undefined' || projectId === 'null') {
      navigate('/', { replace: true });
    }
  }, [projectId, navigate]);

  // AHSP Search & Server-Side Bidirectional Infinite Scroll State
  const [searchQuery, setSearchQuery] = useState('');
  const [activeSearch, setActiveSearch] = useState('');
  const [ahspItems, setAhspItems] = useState([]);
  const [startPage, setStartPage] = useState(1);
  const [endPage, setEndPage] = useState(1);
  const [pageSize] = useState(50); // Limit per 50 items as requested
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingDown, setIsLoadingDown] = useState(false);
  const [isLoadingUp, setIsLoadingUp] = useState(false);
  const [showMasterCollection, setShowMasterCollection] = useState(false);

  // Verification Modal State
  const [selectedCandidateForConfirm, setSelectedCandidateForConfirm] = useState(null);
  const [showConfirmModal, setShowConfirmModal] = useState(false);

  // If no targetRow passed directly, try retrieving from sessionStorage
  useEffect(() => {
    if (!targetRow) {
      const savedRow = sessionStorage.getItem('estimator_ahsp_target_row');
      const savedProj = sessionStorage.getItem('estimator_ahsp_project_id');
      if (savedRow) {
        try {
          const parsed = JSON.parse(savedRow);
          setTargetRow(parsed);
        } catch (e) {
          console.error(e);
        }
      }
      if (savedProj) setProjectId(savedProj);
    } else {
      sessionStorage.setItem('estimator_ahsp_target_row', JSON.stringify(targetRow));
      if (projectId && projectId !== 'default') {
        sessionStorage.setItem('estimator_ahsp_project_id', projectId);
      }
    }
  }, [targetRow, projectId]);

  // Default master collection visibility based on recommendation candidates
  useEffect(() => {
    if (targetRow) {
      const hasCandidates = targetRow.ahsp_candidates && targetRow.ahsp_candidates.length > 0;
      setShowMasterCollection(!hasCandidates);
    }
  }, [targetRow]);

  // Server-Side Data Fetching (Directional: 'down', 'up', or 'reset')
  const fetchAhspDirectional = async (targetPage = 1, direction = 'reset') => {
    if (direction === 'down') {
      setIsLoadingDown(true);
    } else if (direction === 'up') {
      setIsLoadingUp(true);
    } else {
      setIsLoading(true);
    }

    const PYTHON_API_BASE = typeof window !== 'undefined'
      ? `http://${window.location.hostname}:8200`
      : (import.meta.env.VITE_PYTHON_API_URL || 'http://localhost:8200');

    try {
      const url = `${PYTHON_API_BASE}/api/ahsp/list?page=${targetPage}&limit=${pageSize}&search=${encodeURIComponent(activeSearch.trim())}`;
      const res = await fetch(url);
      if (res.ok) {
        const data = await res.json();
        const newItems = data.items || [];
        setTotalCount(data.total || 0);
        setTotalPages(data.total_pages || 1);

        if (direction === 'down') {
          // Append to bottom
          setAhspItems((prev) => [...prev, ...newItems]);
          setEndPage(targetPage);
        } else if (direction === 'up') {
          // Prepend to top & adjust scroll position to stay still
          const container = tableContainerRef.current;
          const oldHeight = container ? container.scrollHeight : 0;
          const oldTop = container ? container.scrollTop : 0;

          setAhspItems((prev) => [...newItems, ...prev]);
          setStartPage(targetPage);

          requestAnimationFrame(() => {
            if (container) {
              const newHeight = container.scrollHeight;
              container.scrollTop = oldTop + (newHeight - oldHeight);
            }
          });
        } else {
          // Reset view (Page 1)
          setAhspItems(newItems);
          setStartPage(1);
          setEndPage(1);
        }
      } else {
        if (direction === 'reset') setAhspItems([]);
      }
    } catch (err) {
      console.error("Error fetching AHSP items server-side:", err);
      if (direction === 'reset') setAhspItems([]);
    } finally {
      setIsLoading(false);
      setIsLoadingDown(false);
      setIsLoadingUp(false);
    }
  };

  // Initial load on toggle
  useEffect(() => {
    if (showMasterCollection && ahspItems.length === 0) {
      fetchAhspDirectional(1, 'reset');
    }
  }, [showMasterCollection]);

  // Bi-Directional Infinite Scroll Trigger (Scroll Down & Scroll Up)
  const handleTableScroll = (e) => {
    const { scrollTop, scrollHeight, clientHeight } = e.target;

    // Scroll Down Trigger (Near bottom: within 100px)
    if (scrollHeight - scrollTop - clientHeight < 100) {
      if (endPage < totalPages && !isLoading && !isLoadingDown && !isLoadingUp) {
        fetchAhspDirectional(endPage + 1, 'down');
      }
    }

    // Scroll Up Trigger (Near top: within 80px)
    if (scrollTop < 80) {
      if (startPage > 1 && !isLoading && !isLoadingDown && !isLoadingUp) {
        fetchAhspDirectional(startPage - 1, 'up');
      }
    }
  };

  const handleSearchSubmit = () => {
    setActiveSearch(searchQuery);
    // Fetch with new query reset
    const PYTHON_API_BASE = typeof window !== 'undefined'
      ? `http://${window.location.hostname}:8200`
      : (import.meta.env.VITE_PYTHON_API_URL || 'http://localhost:8200');

    setIsLoading(true);
    fetch(`${PYTHON_API_BASE}/api/ahsp/list?page=1&limit=${pageSize}&search=${encodeURIComponent(searchQuery.trim())}`)
      .then((res) => res.json())
      .then((data) => {
        setAhspItems(data.items || []);
        setTotalCount(data.total || 0);
        setTotalPages(data.total_pages || 1);
        setStartPage(1);
        setEndPage(1);
      })
      .catch((err) => console.error(err))
      .finally(() => setIsLoading(false));
  };

  const handleResetSearch = () => {
    setSearchQuery('');
    setActiveSearch('');
    fetchAhspDirectional(1, 'reset');
  };

  const handleOpenConfirmModal = (cand) => {
    setSelectedCandidateForConfirm(cand);
    setShowConfirmModal(true);
  };

  const handleApplyAhspSelection = async (ahspItem) => {
    if (!targetRow) return;

    const newUnit = ahspItem.satuan || ahspItem.unit || targetRow.unit || '';
    const newCode = ahspItem.id_pekerjaan || ahspItem.ahsp_code || targetRow.ahsp_code || '';
    const newName = ahspItem.nama_pekerjaan || ahspItem.ahsp_name || targetRow.ahsp_name || '';

    // Identifier could be db_id or id
    const targetIdentifier = targetRow.db_id || targetRow.id;

    // Sync to backend MySQL database if identifier exists
    if (targetIdentifier) {
      try {
        await updateEstimationItem(targetIdentifier, {
          ahsp_code: newCode,
          ahsp_name: newName,
          ahsp_status: 'mapped_high',
          ahsp_unit: newUnit,
          unit: newUnit, // Pastikan satuan pekerjaan berubah mengikuti AHSP yang dipilih
          ahsp_score: 1.0,
        });
      } catch (err) {
        console.warn("Could not update AHSP mapping in backend database:", err.message);
      }
    }

    // Save updated row in sessionStorage so Anggaran.jsx & RabPage.jsx immediately update in UI
    const updatedRow = {
      ...targetRow,
      ahsp_code: newCode,
      ahsp_name: newName,
      ahsp_unit: newUnit,
      unit: newUnit,
      ahsp_status: 'mapped_high',
      ahsp_score: 1.0
    };
    if (targetIdentifier) {
      updateRow(targetIdentifier, {
        ahsp_code: newCode,
        ahsp_name: newName,
        ahsp_unit: newUnit,
        unit: newUnit,
        ahsp_status: 'mapped_high',
        ahsp_score: 1.0
      });
    }
    sessionStorage.setItem('estimator_last_updated_row', JSON.stringify(updatedRow));

    // Save notification toast trigger message
    sessionStorage.setItem('estimator_toast_msg', `Berhasil memetakan "${targetRow.name}" ke "${newName}" (${newUnit})`);

    // Navigate back to origin page (RAB or Anggaran) with matching ?id= parameter
    const returnPath = location.state?.returnUrl || `/anggaran?id=${projectId}`;
    navigate(returnPath);
  };

  // If no projectId provided, do not render contents while redirecting
  if (!projectId || projectId === 'undefined' || projectId === 'null') {
    return null;
  }

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      {/* Top Navigation */}
      <Navbar />

      {/* Hero Banner with Green Wavy Pattern */}
      <div className="w-full relative overflow-hidden h-10 md:h-28 flex items-center justify-center select-none bg-[#84c225] shadow-xs">
        <img
          src={proyekBg}
          alt="Banner Pemetaan AHSP"
          className="absolute inset-0 w-full h-full object-center pointer-events-none"
        />
        <div className="relative z-10 text-center px-4 max-w-2xl">
          <h1 className="text-xl md:text-2xl lg:text-3xl font-semibold text-white tracking-wider uppercase drop-shadow-sm font-sans">
            Pemetaan Item Pekerjaan AHSP
          </h1>
        </div>
      </div>

      {/* Main Content Workspace Card */}
      <main className="max-w-[1360px] mx-auto px-4 mt-6">
        <div className="bg-white rounded-xl shadow-xs border border-slate-200/80 p-5 md:p-6 space-y-4">

          {/* Top Bar: Breadcrumb + Back Button */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-150">
            <div>
              <div className="flex items-center gap-2 text-[11.5px] text-slate-500 mb-0.5">
                <span>Proyek</span>
                <span>/</span>
                <span>Estimasi RAB</span>
                <span>/</span>
                <span className="font-semibold text-[#009624]">Pemetaan Pekerjaan</span>
              </div>
              <h2 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
                <Icons.Book className="w-4.5 h-4.5 text-[#009624]" />
                Pemetaan Item Pekerjaan AHSP
              </h2>
            </div>

            <button
              onClick={() => navigate(location.state?.returnUrl || `/anggaran?id=${projectId}`)}
              className="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold transition-all cursor-pointer shadow-2xs shrink-0 self-start sm:self-auto"
            >
              <Icons.ChevronLeft className="w-4 h-4" />
              Kembali
            </button>
          </div>

          {/* Target Item Summary Banner */}
          {targetRow ? (
            <div className="bg-[#009624] text-white rounded-lg shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
              <div className="space-y-1">
                <h3 className="text-base font-extrabold text-white">{targetRow.ahsp_name}</h3>
                <div className="flex items-center gap-3 text-[12.5px] text-emerald-100/90 flex-wrap">
                  <span>Satuan: <strong className="text-white font-bold"> {targetRow.unit}</strong></span>
                  {targetRow.ahsp_name && (
                    <>
                      <span>•</span>
                      <span>Hasil Deteksi: <strong className="text-amber-200 font-bold">{targetRow.name}</strong></span>
                    </>
                  )}
                </div>
              </div>
            </div>
          ) : (
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-3.5 text-amber-800 text-[12.5px]">
              Tidak ada item pekerjaan yang dipilih. Silakan kembali ke halaman RAB untuk memilih item.
            </div>
          )}

          {/* SECTION 1: DAFTAR REKOMENDASI PEKERJAAN */}
          {targetRow && (
            <div className="space-y-2.5">
              <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 className="text-[13px] font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                  <Icons.Book className="w-4 h-4 text-[#009624]" />
                  Rekomendasi Item Pekerjaan
                </h3>
                {targetRow.ahsp_candidates && targetRow.ahsp_candidates.length > 0 && (
                  <span className="text-[11px] font-bold text-[#007b1e] bg-[#f1faf2] border border-[#cbeed0] px-2.5 py-0.5 rounded-full">
                    {targetRow.ahsp_candidates.length} Pilihan Ditemukan
                  </span>
                )}
              </div>

              {targetRow.ahsp_candidates && targetRow.ahsp_candidates.length > 0 ? (
                <div className="overflow-x-auto border border-slate-200/90 rounded-sm shadow-3xs">
                  <table className="w-full border-collapse text-left text-[12.5px]">
                    <thead className="bg-[#089613] text-white select-none sticky top-0 z-10">
                      <tr>
                        <th scope="col" className="py-2.5 px-3 text-center w-12 font-bold text-[12px] tracking-wide">No.</th>
                        <th scope="col" className="py-2.5 px-4 text-left font-bold text-[12px] tracking-wide">Uraian Pekerjaan Standar AHSP</th>
                        <th scope="col" className="py-2.5 px-3 text-center w-24 font-bold text-[12px] tracking-wide">Satuan</th>
                        <th scope="col" className="py-2.5 px-3 text-center w-28 font-bold text-[12px] tracking-wide">Aksi</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {targetRow.ahsp_candidates.map((cand, idx) => (
                        <tr
                          key={cand.id_pekerjaan || idx}
                          className={`transition-colors group ${idx % 2 === 0 ? 'bg-white hover:bg-[#f1faf2]' : 'bg-[#f8faf9] hover:bg-[#f1faf2]'}`}
                        >
                          <td className="py-2.5 px-3 text-center text-slate-400 font-medium">{idx + 1}</td>
                          <td className="py-2.5 px-4 font-semibold text-slate-800 leading-snug">{cand.nama_pekerjaan}</td>
                          <td className="py-2.5 px-3 text-center text-slate-500 font-medium">{cand.satuan}</td>
                          <td className="py-2.5 px-3 text-center">
                            <button
                              type="button"
                              onClick={() => handleOpenConfirmModal(cand)}
                              className="bg-[#009624] hover:bg-[#007b1e] text-white px-3.5 py-1 rounded-md text-[11.5px] font-bold transition-all shadow-2xs cursor-pointer shrink-0"
                            >
                              Pilih
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="py-5 text-center bg-slate-50 rounded-lg border border-dashed border-slate-200 text-slate-500 text-[12px]">
                  Belum ada rekomendasi pilihan item pekerjaan untuk item ini.
                </div>
              )}
            </div>
          )}

          {/* SECTION 2: SUGGESTION BANNER */}
          <div className="bg-[#f2faf3] border border-[#d3ead6] rounded-lg p-5 text-center space-y-3 flex flex-col items-center justify-center">
            <div className="w-8 h-8 bg-[#009624] text-white rounded-full flex items-center justify-center shadow-3xs">
              <Icons.Info className="w-4.5 h-4.5" />
            </div>

            <p className="text-[13px] text-slate-800 font-semibold leading-relaxed max-w-xl mx-auto">
              {targetRow?.ahsp_candidates && targetRow.ahsp_candidates.length > 0 ? (
                "Apakah hasil rekomendasi di atas belum sesuai? Apabila ingin menemukan item pekerjaan yang lebih tepat, silakan dapat menelusuri daftar item lainnya pada koleksi AHSP."
              ) : (
                "Item pekerjaan ini belum memiliki rekomendasi. Silakan menelusuri daftar item pekerjaan lainnya pada koleksi AHSP."
              )}
            </p>

            <button
              type="button"
              onClick={() => {
                const nextState = !showMasterCollection;
                setShowMasterCollection(nextState);
                if (nextState && ahspItems.length === 0) {
                  fetchAhspDirectional(1, 'reset');
                }
              }}
              className="inline-flex items-center gap-2.5 bg-[#009624] hover:bg-[#007b1e] text-white px-5 py-2.5 rounded-lg text-[13px] font-bold transition-all shadow-md cursor-pointer hover:scale-[1.01]"
            >
              <Icons.Book className="w-4 h-4 text-white" />
              <span>{showMasterCollection ? "Sembunyikan Koleksi Master Data AHSP" : "Telusuri Koleksi Master Data AHSP"}</span>
              <Icons.ChevronDown className={`w-4 h-4 transition-transform duration-200 ${showMasterCollection ? 'rotate-180' : ''}`} />
            </button>
          </div>

          {/* FULL MASTER AHSP COLLECTION SEARCH & BIDIRECTIONAL INFINITE SCROLL DATATABLE */}
          {showMasterCollection && (
            <div className="space-y-4 animate-fade-in">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                <div>
                  <h3 className="text-[13.5px] font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                    <Icons.Book className="w-4.5 h-4.5 text-[#009624]" />
                    Koleksi Data Pekerjaan AHSP
                  </h3>
                </div>
              </div>

              {/* Search Input Bar & Controls */}
              <div className="flex gap-2">
                <div className="relative flex-1">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSearchSubmit()}
                    placeholder="Cari nama item pekerjaan (misal: Pasangan Dinding Bata Merah)..."
                    className="w-full bg-slate-50 border border-slate-300 rounded-lg py-2 pl-9 pr-9 text-[12.5px] text-slate-800 focus:bg-white focus:outline-none focus:border-[#009624] focus:ring-1 focus:ring-[#009624] shadow-3xs"
                  />
                  <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <Icons.Search className="w-4 h-4" />
                  </div>
                  {searchQuery && (
                    <button
                      onClick={handleResetSearch}
                      className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 cursor-pointer"
                      title="Reset"
                    >
                      <Icons.X className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>

                <button
                  onClick={handleSearchSubmit}
                  className="bg-[#009624] hover:bg-[#007b1e] text-white px-4 py-2 rounded-lg text-[12.5px] font-bold transition-colors cursor-pointer shadow-2xs whitespace-nowrap"
                >
                  {isLoading ? 'Mencari...' : 'Cari Data'}
                </button>

                {(searchQuery || activeSearch) && (
                  <button
                    onClick={handleResetSearch}
                    className="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-3.5 py-2 rounded-lg text-[12.5px] font-semibold transition-colors cursor-pointer shadow-2xs whitespace-nowrap"
                  >
                    Reset Search
                  </button>
                )}
              </div>

              {/* Master AHSP Table Container with Bidirectional Infinite Scroll */}
              <div
                ref={tableContainerRef}
                onScroll={handleTableScroll}
                className="border border-slate-200/90 rounded-sm overflow-y-auto max-h-[460px] shadow-3xs relative custom-scrollbar bg-white"
              >
                <table className="w-full text-left text-[12.5px] border-collapse">
                  <thead className="bg-[#089613] text-white text-[12px] font-bold uppercase tracking-wider sticky top-0 z-20 shadow-xs select-none">
                    <tr>
                      <th className="py-2.5 px-3.5 text-center w-14 bg-[#089613]">No.</th>
                      <th className="py-2.5 px-3.5 bg-[#089613]">Uraian Pekerjaan Standar AHSP</th>
                      <th className="py-2.5 px-3.5 w-24 text-center bg-[#089613]">Satuan</th>
                      <th className="py-2.5 px-3.5 w-28 text-center bg-[#089613]">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white">
                    {/* Scroll Up Loading / Prepend Trigger Row */}
                    {startPage > 1 && (
                      isLoadingUp ? (
                        <tr className="bg-[#f1faf2] border-b border-[#cbeed0]">
                          <td colSpan="4" className="py-2.5 text-center text-[12px] font-bold text-[#009624]">
                            <div className="flex items-center justify-center gap-2">
                              <div className="w-4 h-4 border-2 border-[#009624] border-t-transparent rounded-full animate-spin"></div>
                              <span>Memuat data AHSP sebelumnya (Halaman {startPage - 1})...</span>
                            </div>
                          </td>
                        </tr>
                      ) : (
                        <tr
                          onClick={() => fetchAhspDirectional(startPage - 1, 'up')}
                          className="bg-slate-50 border-b border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors"
                        >
                          <td colSpan="4" className="py-2 text-center text-[11.5px] text-[#009624] font-semibold">
                            ▲ Gulir ke atas atau klik di sini untuk memuat data sebelumnya (Halaman 1 - {startPage - 1})
                          </td>
                        </tr>
                      )
                    )}

                    {isLoading && startPage === 1 && ahspItems.length === 0 ? (
                      <tr>
                        <td colSpan="4" className="py-12 text-center text-slate-500 font-semibold">
                          <div className="flex flex-col items-center justify-center gap-2">
                            <div className="w-6 h-6 border-2 border-[#00802b] border-t-transparent rounded-full animate-spin"></div>
                            <span>Memuat data AHSP dari server database...</span>
                          </div>
                        </td>
                      </tr>
                    ) : ahspItems.length === 0 ? (
                      <tr>
                        <td colSpan="4" className="py-12 text-center text-slate-400 font-medium">
                          {activeSearch
                            ? `Tidak ada pekerjaan AHSP yang cocok dengan pencarian "${activeSearch}".`
                            : 'Database pekerjaan AHSP kosong.'}
                        </td>
                      </tr>
                    ) : (
                      ahspItems.map((item, idx) => {
                        const rowNumber = (startPage - 1) * pageSize + idx + 1;
                        return (
                          <tr
                            key={item.id_pekerjaan || idx}
                            className={`transition-colors group ${idx % 2 === 0
                              ? 'bg-white hover:bg-[#f1faf2]'
                              : 'bg-[#f8faf9] hover:bg-[#f1faf2]'
                              }`}
                          >
                            <td className="py-2.5 px-3.5 text-center text-slate-400 font-medium">
                              {rowNumber}
                            </td>
                            <td className="py-2.5 px-3.5 font-semibold text-slate-800 leading-snug">
                              {item.nama_pekerjaan}
                            </td>
                            <td className="py-2.5 px-3.5 text-center text-slate-500 font-medium">
                              {item.satuan}
                            </td>
                            <td className="py-2.5 px-3.5 text-center">
                              <button
                                type="button"
                                onClick={() => handleOpenConfirmModal(item)}
                                className="bg-white border border-[#009624] group-hover:bg-[#009624] group-hover:text-white text-[#009624] px-3 py-1 rounded-md text-[11.5px] font-bold transition-all shadow-2xs cursor-pointer"
                              >
                                Pilih
                              </button>
                            </td>
                          </tr>
                        );
                      })
                    )}
                  </tbody>
                </table>

                {/* Scroll Down Loading status inside scroll container */}
                {isLoadingDown && (
                  <div className="py-3 bg-[#f1faf2] border-t border-[#cbeed0] text-center text-[12px] font-bold text-[#009624] flex items-center justify-center gap-2 sticky bottom-0 z-10">
                    <div className="w-4 h-4 border-2 border-[#009624] border-t-transparent rounded-full animate-spin"></div>
                    <span>Memuat data AHSP berikutnya (Halaman {endPage + 1})...</span>
                  </div>
                )}

                {endPage < totalPages && !isLoadingDown && (
                  <div
                    onClick={() => fetchAhspDirectional(endPage + 1, 'down')}
                    className="py-2.5 bg-slate-50 border-t border-slate-200 text-center text-[11.5px] text-[#009624] font-semibold cursor-pointer hover:bg-slate-100 transition-colors"
                  >
                    ▼ Gulir ke bawah atau klik di sini untuk memuat data berikutnya (+50 item)
                  </div>
                )}

                {endPage >= totalPages && ahspItems.length > 0 && (
                  <div className="py-2.5 bg-slate-50 border-t border-slate-200 text-center text-[11.5px] text-slate-500 font-semibold">
                    ✓ Semuanya telah dimuat ({totalCount.toLocaleString('id-ID')} item AHSP)
                  </div>
                )}
              </div>
            </div>
          )}

        </div>
      </main>

      {/* Verification Confirmation Modal */}
      {showConfirmModal && selectedCandidateForConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 animate-fade-in">
          <div className="bg-white rounded-xl shadow-xl border border-slate-200 max-w-md w-full p-5 space-y-4 transform transition-all scale-100">
            {/* Modal Header */}
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 bg-[#f1faf2] border border-[#cbeed0] text-[#009624] rounded-full flex items-center justify-center shrink-0">
                <Icons.Book className="w-4.5 h-4.5" />
              </div>
              <div>
                <h3 className="text-sm font-extrabold text-slate-900 leading-tight">
                  Konfirmasi Pemetaan Pekerjaan
                </h3>
                <p className="text-[11.5px] text-slate-500">
                  Apakah Anda yakin ingin memilih pekerjaan ini?
                </p>
              </div>
            </div>

            {/* Target vs Selected Item Summary */}
            <div className="bg-slate-50 rounded-lg border border-slate-200 p-3.5 space-y-2.5 text-[12.5px]">
              <div>
                <span className="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">
                  Target Pekerjaan RAB:
                </span>
                <p className="font-semibold text-slate-800 leading-snug">
                  {targetRow?.name}
                </p>
              </div>

              <div className="pt-2 border-t border-slate-200">
                <span className="text-[10.5px] font-bold text-[#007b1e] uppercase tracking-wider block mb-0.5">
                  Pekerjaan AHSP Dipilih:
                </span>
                <p className="font-bold text-slate-800 leading-snug">
                  {selectedCandidateForConfirm.nama_pekerjaan}
                </p>
                <span className="text-[11.5px] text-slate-500 block mt-0.5">
                  Satuan: <strong className="text-slate-700">{selectedCandidateForConfirm.satuan}</strong>
                </span>
              </div>
            </div>

            {/* Modal Action Buttons */}
            <div className="flex items-center justify-end gap-2.5 pt-1">
              <button
                type="button"
                onClick={() => {
                  setShowConfirmModal(false);
                  setSelectedCandidateForConfirm(null);
                }}
                className="px-3.5 py-1.5 rounded-lg text-[12px] font-bold text-slate-600 hover:bg-slate-100 border border-slate-300 transition-colors cursor-pointer"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={() => {
                  handleApplyAhspSelection(selectedCandidateForConfirm);
                  setShowConfirmModal(false);
                }}
                className="px-4 py-1.5 rounded-lg text-[12px] font-bold text-white bg-[#009624] hover:bg-[#007b1e] shadow-xs transition-all cursor-pointer"
              >
                Ya, Simpan Pemetaan
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PemetaanAhsp;
