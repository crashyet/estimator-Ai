import React from 'react';
import { Icons } from '../Icons';

const formatNumber = (value) => {
  return value.toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const WbsSectionTable = ({
  tableContainerRef,
  handleTableScroll,
  displayedRows,
  filteredRows,
  visibleLimit,
  setVisibleLimit,
  handleOpenAhspModal,
  handleDeleteItem
}) => {
  return (
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

              const wbsNumber = row.wbs_code || (row.sectionCode ? `${row.sectionCode}.${row.no}` : row.no);
              const topTitle = row.ahsp_name || row.name;

              return (
                <tr key={row.id} className="hover:bg-slate-50/50 transition-all group duration-150">
                  <td className="py-3 px-4 text-center font-bold text-slate-700 select-none tabular-nums text-[12.5px]">
                    {wbsNumber}
                  </td>
                  <td className="py-3 px-4 max-w-[420px]">
                    <div className="flex flex-col gap-1">
                      <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-semibold text-slate-800 break-words">{topTitle}</span>
                      </div>
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
  );
};

export default WbsSectionTable;
