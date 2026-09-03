import React from 'react';
import { Icons } from '../Icons';

const SummaryHeaderCard = ({ projectDetail, handleExportCSV }) => {
  return (
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
