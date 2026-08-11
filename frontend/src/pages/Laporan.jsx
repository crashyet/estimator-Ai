import React from 'react'
import Navbar from '../components/Navbar'

const Laporan = () => {
  const formatRupiah = (value) => {
    return "Rp " + value.toLocaleString('id-ID', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  // Cost data representation
  const costBreakdown = [
    { name: 'Persiapan', amount: 41723738.01, percent: 4.19, color: 'bg-emerald-500' },
    { name: 'Pondasi & Tanah', amount: 154000000.00, percent: 15.46, color: 'bg-teal-500' },
    { name: 'Struktur Beton L1-L3', amount: 382450000.00, percent: 38.40, color: 'bg-indigo-500' },
    { name: 'Dinding & Plester', amount: 112000000.00, percent: 11.25, color: 'bg-blue-500' },
    { name: 'Atap & Plafon', amount: 165000000.00, percent: 16.57, color: 'bg-pink-500' },
    { name: 'Lain-lain & Sanitair', amount: 140797569.57, percent: 14.13, color: 'bg-amber-500' }
  ]

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      <Navbar />

      {/* Main Title Section */}
      <div className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="w-full bg-[#f1faf2] border border-[#dff3e1] rounded-lg py-4 px-6 flex items-center justify-center shadow-xs">
          <h1 className="text-lg md:text-xl font-bold tracking-wider text-emerald-950 uppercase select-none">
            Analisis & Laporan Anggaran
          </h1>
        </div>
      </div>

      {/* Workspace Container */}
      <main className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left panel: Key Metrics */}
          <div className="bg-white border border-slate-100 rounded-xl shadow-3xs p-6 flex flex-col gap-6 lg:col-span-1">
            <h2 className="text-base font-bold text-slate-800 border-b border-slate-50 pb-3">Ringkasan Biaya</h2>
            
            <div className="flex flex-col gap-1">
              <span className="text-[12.5px] font-semibold text-slate-400 uppercase tracking-wider">Total Anggaran (Grand Total)</span>
              <span className="text-2xl font-black text-emerald-700">{formatRupiah(995971307.58)}</span>
              <p className="text-[11.5px] text-slate-455 mt-1">Estimasi biaya konstruksi gedung berdasarkan perhitungan volume & PPN 0%</p>
            </div>

            <div className="flex flex-col gap-4 pt-4 border-t border-slate-100">
              <div className="flex justify-between items-center text-[13px]">
                <span className="font-semibold text-slate-500">Biaya Material Utama</span>
                <span className="font-bold text-slate-700">{formatRupiah(597582784.55)} (60%)</span>
              </div>
              <div className="flex justify-between items-center text-[13px]">
                <span className="font-semibold text-slate-500">Upah Tenaga Kerja</span>
                <span className="font-bold text-slate-700">{formatRupiah(298791392.27)} (30%)</span>
              </div>
              <div className="flex justify-between items-center text-[13px]">
                <span className="font-semibold text-slate-500">Operasional & Alat</span>
                <span className="font-bold text-slate-700">{formatRupiah(99597130.76)} (10%)</span>
              </div>
            </div>
            
            <button className="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg py-2.5 text-xs font-semibold shadow-sm transition-colors mt-auto flex items-center justify-center gap-1.5 cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2.5" stroke="currentColor" className="w-4 h-4">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
              </svg>
              Ekspor Laporan PDF
            </button>
          </div>

          {/* Right panel: Charts & Breakdown */}
          <div className="bg-white border border-slate-100 rounded-xl shadow-3xs p-6 lg:col-span-2 flex flex-col">
            <h2 className="text-base font-bold text-slate-800 border-b border-slate-50 pb-3 mb-6">Pecahan Anggaran per Bagian Pekerjaan</h2>
            
            {/* Visual Bar Chart */}
            <div className="flex flex-col gap-5 flex-1 justify-center">
              {costBreakdown.map((item, idx) => (
                <div key={idx} className="flex flex-col gap-1.5">
                  <div className="flex justify-between items-center text-[12.5px]">
                    <span className="font-semibold text-slate-700">{item.name}</span>
                    <span className="font-bold text-slate-800">{formatRupiah(item.amount)} <span className="text-slate-400 font-normal ml-1">({item.percent}%)</span></span>
                  </div>
                  
                  {/* Progress bar container */}
                  <div className="w-full h-3 bg-slate-50 border border-slate-100 rounded-full overflow-hidden flex">
                    <div 
                      className={`h-full rounded-full transition-all duration-500 ${item.color}`}
                      style={{ width: `${item.percent}%` }}
                    ></div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}

export default Laporan
