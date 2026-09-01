import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Navbar from '../components/Navbar';
import { Icons } from '../components/Icons';

const PemetaanAhsp = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const tableContainerRef = useRef(null);

  // Extract target row & project context from state or URL query
  const locationState = location.state || {};
  const queryParams = useMemo(() => new URLSearchParams(location.search), [location.search]);
  const [projectId, setProjectId] = useState(() => {
    return (
      queryParams.get('id') ||
      queryParams.get('project') ||
      locationState.projectId ||
      sessionStorage.getItem('estimator_ahsp_project_id') ||
      '1'
    );
  });
  const [targetRow, setTargetRow] = useState(locationState.targetRow || null);

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

  const handleApplyAhspSelection = (ahspItem) => {
    if (!targetRow) return;

    // Load active rows from localStorage
    const storageKey = `estimator_uploaded_rows_${projectId}`;
    const saved = localStorage.getItem(storageKey);
    if (saved) {
      try {
        const rows = JSON.parse(saved);
        const updatedRows = rows.map((r) => {
          if (r.id === targetRow.id) {
            return {
              ...r,
              ahsp_code: ahspItem.id_pekerjaan,
              ahsp_name: ahspItem.nama_pekerjaan,
              ahsp_status: 'mapped_high',
              ahsp_score: ahspItem.score || 1.0,
            };
          }
          return r;
        });
        localStorage.setItem(storageKey, JSON.stringify(updatedRows));
      } catch (e) {
        console.error("Failed to update localStorage rows:", e);
      }
    }

    // Save notification toast trigger message
    sessionStorage.setItem('estimator_toast_msg', `Berhasil memetakan "${targetRow.name}" ke "${ahspItem.nama_pekerjaan}"`);

    // Navigate back to Anggaran page with matching ?id= parameter
    navigate(`/anggaran?id=${projectId}`);
  };

  return (
    <div className="min-h-screen bg-[#f7faf8] flex flex-col font-sans antialiased text-slate-800">
      <Navbar />

      <main className="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-5 space-y-4">
        
        {/* Top Header & Breadcrumb */}
        <div className="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div className="flex items-center gap-2 text-[11.5px] text-slate-500 mb-0.5">
              <span>Proyek</span>
              <span>/</span>
              <span>Estimasi RAB</span>
              <span>/</span>
              <span className="font-semibold text-[#009624]">Pemetaan Pekerjaan</span>
            </div>
            <h1 className="text-lg font-extrabold text-slate-900 flex items-center gap-2">
              <Icons.Book className="w-4.5 h-4.5 text-[#009624]" />
              Pemetaan Item Pekerjaan AHSP
            </h1>
          </div>

          <button
            onClick={() => navigate(`/anggaran?id=${projectId}`)}
            className="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold transition-all cursor-pointer shadow-2xs shrink-0 self-start sm:self-auto"
          >
            <Icons.ChevronLeft className="w-4 h-4" />
            Kembali ke RAB
          </button>
        </div>

        {/* Target Item Summary Banner (Compact Signature Green #009624) */}
        {targetRow ? (
          <div className="bg-[#009624] text-white rounded-xl shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div className="space-y-1">
              <h2 className="text-base font-extrabold text-white">{targetRow.name}</h2>
              <div className="flex items-center gap-3 text-[12.5px] text-emerald-100/90 flex-wrap">
                <span>Satuan: <strong className="text-white font-bold"> {targetRow.unit}</strong></span>
                {targetRow.ahsp_name && (
                  <>
                    <span>•</span>
                    <span>Pemetaan Saat Ini: <strong className="text-amber-200 font-bold">{targetRow.ahsp_name}</strong></span>
                  </>
                )}
              </div>
            </div>
          </div>
        ) : (
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-amber-800 text-[12.5px]">
            Tidak ada item pekerjaan yang dipilih. Silakan kembali ke halaman RAB untuk memilih item.
          </div>
        )}

        {/* SECTION 1: DAFTAR REKOMENDASI PEKERJAAN (Compact, Natural Layout) */}
        {targetRow && (
          <div className="bg-white rounded-xl shadow-xs border border-slate-200/90 p-4 space-y-2.5">
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
              <div className="space-y-1.5">
                {targetRow.ahsp_candidates.map((cand, idx) => (
                  <div
                    key={cand.id_pekerjaan || idx}
                    className={`py-2 px-3 rounded-lg border-l-3 border-l-[#009624] border-t border-r border-b border-slate-200/80 transition-all flex items-center justify-between gap-3 hover:border-slate-300 ${
                      idx % 2 === 0 ? 'bg-[#f9faf9]' : 'bg-[#ffffff]'
                    }`}
                  >
                    <div className="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-3 pr-2">
                      <h4 className="font-semibold text-slate-800 text-[12.5px] leading-snug">
                        {cand.nama_pekerjaan}
                      </h4>
                      <div className="text-[11px] text-slate-500 shrink-0 flex items-center gap-1">
                        <span>Satuan:</span>
                        <span className="font-semibold text-slate-700 bg-slate-100 border border-slate-200/80 px-1.5 py-0.2 rounded text-[10.5px]">
                          {cand.satuan}
                        </span>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleOpenConfirmModal(cand)}
                      className="bg-[#009624] hover:bg-[#007b1e] text-white px-3.5 py-1 rounded-md text-[11.5px] font-bold transition-all shadow-2xs cursor-pointer shrink-0"
                    >
                      Pilih
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="py-5 text-center bg-slate-50 rounded-lg border border-dashed border-slate-200 text-slate-500 text-[12px]">
                Belum ada rekomendasi pilihan item pekerjaan untuk item ini.
              </div>
            )}
          </div>
        )}

        {/* SECTION 2: SUGGESTION BANNER */}
        <div className="bg-[#f2faf3] border border-[#d3ead6] rounded-2xl p-6 text-center space-y-4 shadow-3xs flex flex-col items-center justify-center">
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
            className="inline-flex items-center gap-2.5 bg-[#009624] hover:bg-[#007b1e] text-white px-5 py-2.5 rounded-xl text-[13px] font-bold transition-all shadow-md cursor-pointer hover:scale-[1.01]"
          >
            <Icons.Book className="w-4 h-4 text-white" />
            <span>{showMasterCollection ? "Sembunyikan Koleksi Master Data AHSP" : "Telusuri Koleksi Master Data AHSP"}</span>
            <Icons.ChevronDown className={`w-4 h-4 transition-transform duration-200 ${showMasterCollection ? 'rotate-180' : ''}`} />
          </button>
        </div>

        {/* FULL MASTER AHSP COLLECTION SEARCH & BIDIRECTIONAL INFINITE SCROLL DATATABLE */}
        {showMasterCollection && (
          <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-5 space-y-4 animate-fade-in">
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

            {/* Master AHSP Table Container with Bidirectional Infinite Scroll (Scroll Down & Scroll Up) */}
            <div
              ref={tableContainerRef}
              onScroll={handleTableScroll}
              className="border border-slate-200 rounded-lg overflow-y-auto max-h-[460px] shadow-2xs relative custom-scrollbar bg-white"
            >
              <table className="w-full text-left text-[12.5px] border-collapse">
                <thead className="bg-[#009624] text-white text-[11.5px] font-bold uppercase tracking-wider sticky top-0 z-20 shadow-xs">
                  <tr>
                    <th className="py-2.5 px-3.5 text-center w-14 bg-[#009624]">No.</th>
                    <th className="py-2.5 px-3.5 bg-[#009624]">Uraian Pekerjaan Standar AHSP</th>
                    <th className="py-2.5 px-3.5 w-24 text-center bg-[#009624]">Satuan</th>
                    <th className="py-2.5 px-3.5 w-28 text-center bg-[#009624]">Aksi</th>
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
                          <div className="w-6 h-6 border-2 border-[#009624] border-t-transparent rounded-full animate-spin"></div>
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
                          className={`transition-colors group ${
                            idx % 2 === 0
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

      </main>

      {/* Verification Confirmation Modal (Clean & Natural, No AI terms, No Code Tag) */}
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
