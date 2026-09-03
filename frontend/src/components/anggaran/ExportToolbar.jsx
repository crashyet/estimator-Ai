import React from 'react';
import { Icons } from '../Icons';

const ExportToolbar = ({ searchQuery, setSearchQuery, setCurrentPage }) => {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 mb-5">
      <div className="flex items-center gap-2.5 w-full sm:w-auto max-w-xs">
        <span className="text-[13px] text-slate-600 font-medium whitespace-nowrap">Cari Data:</span>
        <div className="relative w-full">
          <input
            type="text"
            placeholder="Cari nama pekerjaan..."
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              if (setCurrentPage) setCurrentPage(1);
            }}
            className="w-full bg-white border border-slate-200 rounded-md py-1.5 pl-3 pr-9 text-[13px] text-slate-700 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-2xs transition-colors"
          />
          <div className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
            <Icons.Search className="w-4 h-4" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default ExportToolbar;
