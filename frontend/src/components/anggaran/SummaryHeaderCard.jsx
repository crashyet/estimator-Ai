import React from 'react';
import { Icons } from '../Icons';

const SummaryHeaderCard = ({ projectDetail, totalBudget, latestRun, handleExportCSV }) => {
  const formatRupiah = (val) => {
    return 'Rp ' + (Number(val) || 0).toLocaleString('id-ID', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  return (
    <div className="max-w-[1240px] mx-auto px-4 mt-6 no-print">
      <div className="w-full bg-[#f1faf2] border border-[#dff3e1] rounded-lg py-4 px-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
        <div>
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-[10px] font-extrabold text-emerald-700 bg-emerald-100/50 px-2.5 py-1 rounded-full uppercase tracking-wider">
              Klien: {projectDetail.client || 'Klien Internal'}
            </span>
            {projectDetail.status && (
              <span className="text-[10px] font-bold text-slate-700 bg-white border border-slate-200 px-2 py-0.5 rounded-full">
                Status: {projectDetail.status}
              </span>
            )}
            {totalBudget > 0 && (
              <span className="text-[10.5px] font-bold text-emerald-900 bg-emerald-200/60 px-2.5 py-0.5 rounded-full">
                Total Anggaran: {formatRupiah(totalBudget)}
              </span>
            )}
          </div>
          <h1 className="text-base md:text-lg font-bold tracking-wide text-emerald-950 uppercase mt-2 select-none">
            {projectDetail.title}
          </h1>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
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
  );
};

export default SummaryHeaderCard;
