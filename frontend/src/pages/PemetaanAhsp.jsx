import React, { useState, useEffect, useMemo } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Navbar from '../components/Navbar';
import { Icons } from '../components/Icons';

const PemetaanAhsp = () => {
  const navigate = useNavigate();
  const location = useLocation();

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

  // AHSP Search & Collection State (searchQuery defaults to empty to show all items)
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [coreQuery, setCoreQuery] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [showMasterCollection, setShowMasterCollection] = useState(false);

  // Verification Modal State
  const [selectedCandidateForConfirm, setSelectedCandidateForConfirm] = useState(null);
  const [showConfirmModal, setShowConfirmModal] = useState(false);

  // Pagination State for Master AHSP Collection
  const [masterPage, setMasterPage] = useState(1);
  const [masterPageSize, setMasterPageSize] = useState(15);

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
      fetchAhspSearch(''); // Fetch all data initially
    }
  }, [targetRow]);

  const fetchAhspSearch = async (query) => {
    setIsSearching(true);
    setMasterPage(1); // Reset to page 1 on new search or reset
    const PYTHON_API_BASE = typeof window !== 'undefined' ? `http://${window.location.hostname}:8200` : (import.meta.env.VITE_PYTHON_API_URL || 'http://localhost:8200');
    try {
      const url = query && query.trim()
        ? `${PYTHON_API_BASE}/api/ahsp/search?q=${encodeURIComponent(query.trim())}&limit=500`
        : `${PYTHON_API_BASE}/api/ahsp/items?limit=3000`;
      const res = await fetch(url);
      if (res.ok) {
        const data = await res.json();
        setSearchResults(data.items || []);
        if (data.core_query) setCoreQuery(data.core_query);
      } else {
        setSearchResults([]);
      }
    } catch (err) {
      console.error("Error fetching AHSP search:", err);
      setSearchResults([]);
    } finally {
      setIsSearching(false);
    }
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

  // Pagination Calculations
  const totalMasterItems = searchResults.length;
  const totalMasterPages = Math.max(1, Math.ceil(totalMasterItems / masterPageSize));
  const activeMasterPage = Math.min(masterPage, totalMasterPages);

  const paginatedResults = useMemo(() => {
    const start = (activeMasterPage - 1) * masterPageSize;
    return searchResults.slice(start, start + masterPageSize);
  }, [searchResults, activeMasterPage, masterPageSize]);

  const masterPaginationRange = useMemo(() => {
    const delta = 1;
    const range = [];
    for (let i = 1; i <= totalMasterPages; i++) {
      if (i === 1 || i === totalMasterPages || (i >= activeMasterPage - delta && i <= activeMasterPage + delta)) {
        range.push(i);
      } else if (range[range.length - 1] !== '...') {
        range.push('...');
      }
    }
    return range;
  }, [activeMasterPage, totalMasterPages]);

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
              <span className="inline-flex items-center gap-1.5 bg-black/20 text-white border border-white/20 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider">
                Target Pekerjaan RAB
              </span>
              <h2 className="text-base font-extrabold text-white">{targetRow.name}</h2>
              <div className="flex items-center gap-3 text-[12.5px] text-emerald-100/90 flex-wrap">
                <span>Volume: <strong className="text-white font-bold">{targetRow.volume} {targetRow.unit}</strong></span>
                {targetRow.ahsp_name && (
                  <>
                    <span>•</span>
                    <span>Pemetaan Saat Ini: <strong className="text-amber-200 font-bold">{targetRow.ahsp_name}</strong></span>
                  </>
                )}
              </div>
            </div>

            <div className="bg-black/20 rounded-lg py-2 px-3 border border-white/20 shadow-2xs text-right shrink-0">
              <span className="text-[10.5px] text-emerald-100 block font-medium">Status Pemetaan</span>
              <span className="text-[12.5px] font-extrabold text-white uppercase tracking-wide">
                {targetRow.ahsp_status ? targetRow.ahsp_status.replace('_', ' ') : 'Belum Dipetakan'}
              </span>
            </div>
          </div>
        ) : (
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-amber-800 text-[12.5px]">
            Tidak ada item pekerjaan yang dipilih. Silakan kembali ke halaman RAB untuk memilih item.
          </div>
        )}

        {/* SECTION 1: DAFTAR REKOMENDASI PEKERJAAN (Compact, Natural, No AI mention, No Score, No Code Badge) */}
        {targetRow && (
          <div className="bg-white rounded-xl shadow-xs border border-slate-200/90 p-5 space-y-3">
            <div className="flex items-center justify-between border-b border-slate-100 pb-2.5">
              <h3 className="text-[13.5px] font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <Icons.Book className="w-4.5 h-4.5 text-[#009624]" />
                Rekomendasi Item Pekerjaan
              </h3>
              {targetRow.ahsp_candidates && targetRow.ahsp_candidates.length > 0 && (
                <span className="text-[11.5px] font-bold text-[#007b1e] bg-[#f1faf2] border border-[#cbeed0] px-2.5 py-0.5 rounded-full">
                  {targetRow.ahsp_candidates.length} Pilihan Ditemukan
                </span>
              )}
            </div>

            {targetRow.ahsp_candidates && targetRow.ahsp_candidates.length > 0 ? (
              <div className="space-y-2">
                {targetRow.ahsp_candidates.map((cand, idx) => (
                  <div
                    key={cand.id_pekerjaan || idx}
                    className={`p-3 rounded-lg border-l-4 border-l-[#009624] border-t border-r border-b border-slate-200/80 transition-all flex items-center justify-between gap-3 ${
                      idx % 2 === 0 ? 'bg-[#f9faf9]' : 'bg-white'
                    }`}
                  >
                    <div className="flex-1 min-w-0">
                      <h4 className="font-semibold text-slate-800 text-[13px] leading-snug">
                        {cand.nama_pekerjaan}
                      </h4>
                      <div className="text-[11.5px] text-slate-500 mt-0.5">
                        Satuan: <strong className="text-slate-700 font-semibold">{cand.satuan}</strong>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => handleOpenConfirmModal(cand)}
                      className="bg-[#009624] hover:bg-[#007b1e] text-white px-4 py-1.5 rounded-md text-[12px] font-bold transition-all shadow-2xs cursor-pointer shrink-0"
                    >
                      Pilih
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="py-6 text-center bg-slate-50 rounded-lg border border-dashed border-slate-200 text-slate-500 text-[12.5px]">
                Belum ada rekomendasi pilihan item pekerjaan untuk item ini.
              </div>
            )}
          </div>
        )}

        {/* SECTION 2: SUGGESTION BANNER (Exact Match with Design Screenshot) */}
        <div className="bg-[#f2faf3] border border-[#d3ead6] rounded-2xl p-6 text-center space-y-4 shadow-3xs flex flex-col items-center justify-center">
          <div className="w-8 h-8 bg-[#009624] text-white rounded-full flex items-center justify-center shadow-3xs">
            <Icons.Info className="w-4.5 h-4.5" />
          </div>

          <p className="text-[13px] text-slate-800 font-semibold leading-relaxed max-w-xl mx-auto">
            Apakah hasil rekomendasi di atas belum sesuai? Apabila ingin menemukan item pekerjaan yang lebih tepat, silakan dapat menelusuri daftar item lainnya pada koleksi AHSP.
          </p>

          <button
            type="button"
            onClick={() => {
              const nextState = !showMasterCollection;
              setShowMasterCollection(nextState);
              if (nextState && searchResults.length === 0) {
                fetchAhspSearch(searchQuery);
              }
            }}
            className="inline-flex items-center gap-2.5 bg-[#009624] hover:bg-[#007b1e] text-white px-5 py-2.5 rounded-xl text-[13px] font-bold transition-all shadow-md cursor-pointer hover:scale-[1.01]"
          >
            <Icons.Book className="w-4 h-4 text-white" />
            <span>{showMasterCollection ? "Sembunyikan Koleksi Master Data AHSP" : "Telusuri Koleksi Master Data AHSP"}</span>
            <Icons.ChevronDown className={`w-4 h-4 transition-transform duration-200 ${showMasterCollection ? 'rotate-180' : ''}`} />
          </button>
        </div>

        {/* FULL MASTER AHSP COLLECTION SEARCH, TABLE & PAGINATION */}
        {showMasterCollection && (
          <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-5 space-y-4 animate-fade-in">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
              <h3 className="text-[13.5px] font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <Icons.Book className="w-4.5 h-4.5 text-[#009624]" />
                Koleksi Data Pekerjaan AHSP ({totalMasterItems.toLocaleString('id-ID')} Item)
              </h3>
              
              <div className="flex items-center gap-3">
                <div className="flex items-center gap-1.5 text-[11.5px] text-slate-500 font-medium">
                  <span>Tampilkan:</span>
                  <select
                    value={masterPageSize}
                    onChange={(e) => {
                      setMasterPageSize(Number(e.target.value));
                      setMasterPage(1);
                    }}
                    className="bg-slate-50 border border-slate-300 rounded px-2 py-1 text-[11.5px] font-semibold text-slate-700 focus:outline-none focus:border-[#009624]"
                  >
                    <option value={10}>10 baris</option>
                    <option value={15}>15 baris</option>
                    <option value={25}>25 baris</option>
                    <option value={50}>50 baris</option>
                    <option value={100}>100 baris</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Search Input Bar & Controls */}
            <div className="flex gap-2">
              <div className="relative flex-1">
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && fetchAhspSearch(searchQuery)}
                  placeholder="Cari nama item pekerjaan (misal: Pasangan Dinding Bata Merah)..."
                  className="w-full bg-slate-50 border border-slate-300 rounded-lg py-2 pl-9 pr-9 text-[12.5px] text-slate-800 focus:bg-white focus:outline-none focus:border-[#009624] focus:ring-1 focus:ring-[#009624] shadow-3xs"
                />
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                  <Icons.Search className="w-4 h-4" />
                </div>
                {searchQuery && (
                  <button
                    onClick={() => {
                      setSearchQuery('');
                      fetchAhspSearch('');
                    }}
                    className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 cursor-pointer"
                    title="Reset"
                  >
                    <Icons.X className="w-3.5 h-3.5" />
                  </button>
                )}
              </div>

              <button
                onClick={() => fetchAhspSearch(searchQuery)}
                className="bg-[#009624] hover:bg-[#007b1e] text-white px-4 py-2 rounded-lg text-[12.5px] font-bold transition-colors cursor-pointer shadow-2xs whitespace-nowrap"
              >
                {isSearching ? 'Mencari...' : 'Cari Data'}
              </button>

              {searchQuery && (
                <button
                  onClick={() => {
                    setSearchQuery('');
                    fetchAhspSearch('');
                  }}
                  className="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-3.5 py-2 rounded-lg text-[12.5px] font-semibold transition-colors cursor-pointer shadow-2xs whitespace-nowrap"
                >
                  Tampilkan Semua
                </button>
              )}
            </div>

            {/* Master AHSP Table (Clean, Compact, No Code Column Display) */}
            <div className="border border-slate-200 rounded-lg overflow-hidden shadow-2xs">
              <div className="overflow-x-auto min-h-[260px]">
                <table className="w-full text-left text-[12.5px] border-collapse">
                  <thead className="bg-[#009624] text-white text-[11.5px] font-bold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                      <th className="py-2.5 px-3.5 text-center w-12">No.</th>
                      <th className="py-2.5 px-3.5">Uraian Pekerjaan Standar AHSP</th>
                      <th className="py-2.5 px-3.5 w-24 text-center">Satuan</th>
                      <th className="py-2.5 px-3.5 w-28 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white">
                    {isSearching ? (
                      <tr>
                        <td colSpan="4" className="py-10 text-center text-slate-400 font-medium">
                          Memuat data item pekerjaan...
                        </td>
                      </tr>
                    ) : totalMasterItems === 0 ? (
                      <tr>
                        <td colSpan="4" className="py-10 text-center text-slate-400 font-medium">
                          {searchQuery
                            ? `Tidak ada pekerjaan yang cocok dengan "${searchQuery}".`
                            : 'Database pekerjaan kosong atau belum termuat.'}
                        </td>
                      </tr>
                    ) : (
                      paginatedResults.map((item, idx) => {
                        const rowNumber = (activeMasterPage - 1) * masterPageSize + idx + 1;
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
              </div>
            </div>

            {/* Pagination Controls Bar */}
            {totalMasterItems > 0 && (
              <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <div className="text-[11.5px] text-slate-500 font-medium">
                  Menampilkan <span className="font-semibold text-slate-700">{Math.min(totalMasterItems, (activeMasterPage - 1) * masterPageSize + 1)}</span> - <span className="font-semibold text-slate-700">{Math.min(totalMasterItems, activeMasterPage * masterPageSize)}</span> dari <span className="font-semibold text-slate-700">{totalMasterItems.toLocaleString('id-ID')}</span> item
                </div>

                <div className="flex items-center gap-1.5">
                  <button
                    disabled={activeMasterPage === 1}
                    onClick={() => setMasterPage((prev) => Math.max(1, prev - 1))}
                    className="px-3 py-1 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-[11.5px] font-bold shadow-3xs cursor-pointer transition-colors"
                  >
                    Sebelumnya
                  </button>

                  <div className="flex items-center gap-1">
                    {masterPaginationRange.map((page, idx) => {
                      if (page === '...') {
                        return (
                          <span key={`dots-${idx}`} className="px-1.5 text-slate-400 select-none text-[11px]">...</span>
                        );
                      }
                      const isActive = page === activeMasterPage;
                      return (
                        <button
                          key={`page-${page}`}
                          onClick={() => setMasterPage(page)}
                          className={`w-7 h-7 rounded-md flex items-center justify-center font-bold text-[11.5px] transition-all cursor-pointer ${
                            isActive
                              ? 'bg-[#009624] text-white shadow-xs'
                              : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                          }`}
                        >
                          {page}
                        </button>
                      );
                    })}
                  </div>

                  <button
                    disabled={activeMasterPage === totalMasterPages}
                    onClick={() => setMasterPage((prev) => Math.min(totalMasterPages, prev + 1))}
                    className="px-3 py-1 rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-[11.5px] font-bold shadow-3xs cursor-pointer transition-colors"
                  >
                    Selanjutnya
                  </button>
                </div>
              </div>
            )}

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
