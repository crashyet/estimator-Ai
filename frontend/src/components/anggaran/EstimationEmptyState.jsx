import React, { useState, useRef } from 'react';
import { Icons } from '../Icons';
import {
  analyzeDED,
  analyzePrompt,
  saveProjectEstimation
} from '../../services/api';

const TRIVIA_SLIDES = [
  {
    title: "💡 Tips Kecepatan Konversi",
    text: "Konversi file Revit (.rvt) via Autodesk Cloud API memerlukan pemrosesan yang intensif (bisa memakan waktu 3 s/d 10 menit). Untuk estimasi instan (< 30 detik), ekspor proyek Anda ke format IFC (.ifc) langsung dari software Revit Anda, lalu unggah file IFC tersebut di sini!"
  },
  {
    title: "🏗️ Mengapa Harus Data Asli?",
    text: "Sistem Estimasi kami menerapkan kebijakan '100% Real Data'. AI Gemini tidak akan mengarang volume atau luas secara sembarang, melainkan memetakan kuantitas nyata yang diekstrak secara matematis oleh mesin parser dari model 3D Anda."
  },
  {
    title: "📊 Penomoran WBS & AHSP",
    text: "Setiap elemen pekerjaan yang dihasilkan AI dikelompokkan secara terstruktur berdasarkan standar Analisis Harga Satuan Pekerjaan (AHSP) Indonesia untuk memudahkan penyusunan RAB yang legal."
  },
  {
    title: "⚡ Alur Pemrosesan Model 3D",
    text: "Pertama, file RVT diunggah ke cloud Autodesk untuk dikonversi ke IFC. Kedua, sistem mengekstrak geometri dan volume fisik elemen beton/dinding. Terakhir, Gemini AI menghitung material turunannya (seperti bekisting & rebar) dan memetakan deskripsi RAB."
  },
  {
    title: "📐 Tips Akurasi Volume",
    text: "Pastikan komponen utama bangunan seperti sloof, kolom, balok, pelat lantai, dan dinding di-assign dengan material yang tepat pada perangkat Revit Anda agar volume 3D terhitung dengan akurasi 100%."
  }
];

// Rekomendasi Prompt Konsep / Spesifikasi Bangunan
const PROMPT_RECOMMENDATIONS = [
  {
    id: 'rumah-2-lantai',
    title: 'Rumah 2 Lantai (8x15 m)',
    badge: 'Populer',
    prompt: 'Pembangunan rumah tinggal minimalis modern 2 lantai ukuran 8x15 meter. Lantai 1: carport, teras, ruang tamu, ruang keluarga, 1 kamar tidur utama dengan kamar mandi dalam, dapur bersih, dan toilet tamu. Lantai 2: 2 kamar tidur anak, 1 kamar mandi luar, ruang keluarga, dan area jemur terbuka. Struktur beton bertulang K-250, dinding bata ringan plester aci finishing cat emulsi, lantai granit 60x60, atap rangka baja ringan genteng keramik, dan kusen aluminium.'
  },
  {
    id: 'rumah-1-lantai',
    title: 'Rumah 1 Lantai (6x12 m)',
    badge: 'Standar',
    prompt: 'Pembangunan rumah tinggal sederhana 1 lantai tipe 45 ukuran kavling 6x12 meter. Terdiri dari teras depan, ruang tamu, ruang makan, 2 kamar tidur, 1 kamar mandi, dapur, dan halaman belakang. Pondasi batu kali lajur, struktur kolom praktis beton, dinding bata ringan diplester dan diaci, lantai keramik 40x40, atap rangka baja ringan penutup genteng metal berpasir, dan plafon gypsum board.'
  },
  {
    id: 'ruko-2-lantai',
    title: 'Ruko 2 Lantai (5x16 m)',
    badge: 'Komersial',
    prompt: 'Pembangunan ruko (rumah toko) 2 lantai ukuran 5x16 meter. Lantai 1 difungsikan untuk ruang usaha terbuka/plong, 1 kamar mandi, dan area tangga beton. Lantai 2 difungsikan untuk ruang kantor dengan 2 ruangan partisi gypsum, 1 kamar mandi, dan pantry. Menggunakan pondasi footplat beton bertulang, struktur beton bertulang, pintu depan folding gate besi, lantai homogenous tile 60x60, atap dak beton dan spandek.'
  },
  {
    id: 'renovasi-dapur-km',
    title: 'Renovasi Dapur & Kamar Mandi',
    badge: 'Renovasi',
    prompt: 'Pekerjaan renovasi area dapur bersih dan 1 kamar mandi utama ukuran 3x5 meter. Meliputi pembongkaran keramik lama dan dinding partisi, pemasangan instalasi pipa air bersih dan air kotor baru, pemasangan meja dapur cor beton finishing granit, kitchen sink stainless steel, keramik dinding dapur subway tile 10x20, keramik lantai kamar mandi anti-slip 30x30, dinding kamar mandi full keramik sampai plafon, kloset duduk, shower set, dan plafon PVC anti air.'
  }
];

// Line-art illustration for Upload Dokumen DED
const DedIllustration = () => (
  <svg viewBox="0 0 240 140" className="w-48 h-28 mx-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M20 125H220" stroke="#1e293b" strokeWidth="2.5" strokeLinecap="round" />
    <path d="M35 125L25 135M205 125L215 135" stroke="#64748b" strokeWidth="2" strokeLinecap="round" />
    <rect x="40" y="55" width="85" height="58" rx="4" fill="#f8fafc" stroke="#1e293b" strokeWidth="2" />
    <path d="M48 68H85M48 78H115M48 88H100M48 98H75" stroke="#94a3b8" strokeWidth="1.8" strokeLinecap="round" strokeDasharray="3 3" />
    <path d="M95 110L145 110L120 60Z" fill="#eff6ff" stroke="#2563eb" strokeWidth="2" strokeLinejoin="round" />
    <circle cx="120" cy="90" r="8" fill="white" stroke="#2563eb" strokeWidth="1.5" />
    <path d="M102 110V105M110 110V107M118 110V105M126 110V107M134 110V105" stroke="#2563eb" strokeWidth="1.5" strokeLinecap="round" />
    <path d="M165 45L195 30L220 45L190 60Z" fill="#dcfce7" stroke="#15803d" strokeWidth="2" />
    <path d="M165 45V80L190 95V60Z" fill="#bbf7d0" stroke="#15803d" strokeWidth="2" />
    <path d="M190 60V95L220 80V45Z" fill="#86efac" stroke="#15803d" strokeWidth="2" />
    <circle cx="170" cy="98" r="14" fill="#089613" />
    <path d="M170 104V92M165 97L170 92L175 97" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
  </svg>
);

// Line-art illustration for AI Prompt & Concept
const AiPromptIllustration = () => (
  <svg viewBox="0 0 240 140" className="w-48 h-28 mx-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M20 125H220" stroke="#1e293b" strokeWidth="2.5" strokeLinecap="round" />
    <rect x="55" y="85" width="40" height="35" rx="3" fill="#fef3c7" stroke="#b45309" strokeWidth="2" />
    <rect x="65" y="55" width="35" height="30" rx="3" fill="#fde68a" stroke="#b45309" strokeWidth="2" />
    <path d="M72 70H88M62 102H88" stroke="#d97706" strokeWidth="1.5" strokeLinecap="round" />
    <path d="M140 125V95C140 85 148 80 158 80C168 80 176 85 176 95V125" stroke="#1e293b" strokeWidth="2.2" strokeLinecap="round" />
    <circle cx="158" cy="62" r="12" fill="#f8fafc" stroke="#1e293b" strokeWidth="2.2" />
    <path d="M140 92L110 75M176 92L195 80" stroke="#1e293b" strokeWidth="2" strokeLinecap="round" />
    <path d="M110 50L112 40L117 38L112 36L110 26L108 36L103 38L108 40Z" fill="#f59e0b" stroke="#d97706" strokeWidth="1.2" />
    <path d="M195 45L196.5 37L201 35.5L196.5 34L195 26L193.5 34L189 35.5L193.5 37Z" fill="#10b981" stroke="#059669" strokeWidth="1.2" />
    <circle cx="128" cy="32" r="3" fill="#3b82f6" />
    <circle cx="185" cy="65" r="2.5" fill="#f59e0b" />
    <path d="M102 75C100 68 105 60 115 58" stroke="#f59e0b" strokeWidth="1.5" strokeDasharray="2 2" strokeLinecap="round" />
  </svg>
);

const EstimationEmptyState = ({
  projectId,
  projectDetail,
  onEstimationSuccess,
  triggerToast
}) => {
  // Modal state
  const [showModal, setShowModal] = useState(false);
  const [inputMode, setInputMode] = useState('file'); // 'file' | 'prompt'
  const [file, setFile] = useState(null);
  const [promptText, setPromptText] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [progressPercent, setProgressPercent] = useState(0);
  const [elapsedTime, setElapsedTime] = useState(0);
  const [activeStepIdx, setActiveStepIdx] = useState(0);
  const [currentTriviaIdx, setCurrentTriviaIdx] = useState(0);
  const [apiError, setApiError] = useState(null);

  const fileInputRef = useRef(null);

  const loadingSteps = inputMode === 'prompt'
    ? [
      { label: 'Menganalisis Deskripsi Konsep Bangunan' },
      { label: 'Menentukan Komponen Fisik & Struktur Utama' },
      { label: 'Menghasilkan Struktur WBS Sesuai Standar' },
      { label: 'Memetakan ke Standar AHSP & Analisis Biaya' }
    ]
    : [
      { label: 'Mengunggah & Memvalidasi File Dokumen DED' },
      { label: 'Mengekstrak Komponen & Geometri Model' },
      { label: 'Menghitung Volume Fisik & Kebutuhan Material' },
      { label: 'Memetakan ke Standar AHSP & Analisis Biaya' }
    ];

  // Format timer helper (00:00)
  const formatTimer = (sec) => {
    const m = Math.floor(sec / 60).toString().padStart(2, '0');
    const s = (sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  };

  const handleOpenModal = (mode) => {
    setInputMode(mode);
    setApiError(null);
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    if (e) e.preventDefault();
    setApiError(null);

    const projectName = projectDetail?.title || 'Proyek Tanpa Nama';
    const projectClient = projectDetail?.client || 'Klien Umum';
    const targetId = projectDetail?.uuid || projectDetail?.id || projectId;

    if (inputMode === 'file' && !file) {
      if (triggerToast) triggerToast("Silakan pilih file dokumen DED terlebih dahulu.", "warning");
      return;
    }

    if (inputMode === 'prompt' && (!promptText || promptText.trim().length < 15)) {
      if (triggerToast) triggerToast("Deskripsi prompt terlalu singkat (minimal 15 karakter).", "warning");
      return;
    }

    setIsProcessing(true);
    setProgressPercent(8);
    setElapsedTime(0);
    setActiveStepIdx(0);
    setCurrentTriviaIdx(0);

    // Live timer
    const timerInterval = setInterval(() => {
      setElapsedTime(prev => prev + 1);
    }, 1000);

    // Trivia rotator
    const triviaInterval = setInterval(() => {
      setCurrentTriviaIdx(prev => (prev + 1) % TRIVIA_SLIDES.length);
    }, 6000);

    // Dynamic progress bar
    const progressInterval = setInterval(() => {
      setProgressPercent(prev => {
        if (prev < 30) {
          setActiveStepIdx(0);
          return prev + 4;
        } else if (prev < 60) {
          setActiveStepIdx(1);
          return prev + 2;
        } else if (prev < 85) {
          setActiveStepIdx(2);
          return prev + 1;
        } else if (prev < 95) {
          setActiveStepIdx(3);
          return prev + 0.5;
        }
        return prev;
      });
    }, 450);

    try {
      let aiResult;
      if (inputMode === 'prompt') {
        aiResult = await analyzePrompt(projectName, projectClient, promptText);
      } else {
        aiResult = await analyzeDED(projectName, projectClient, file);
      }

      setProgressPercent(98);
      setActiveStepIdx(3);

      // Save estimation run to project in database
      await saveProjectEstimation(targetId, aiResult);

      setProgressPercent(100);

      clearInterval(timerInterval);
      clearInterval(triviaInterval);
      clearInterval(progressInterval);

      setIsProcessing(false);
      setShowModal(false);
      setFile(null);
      setPromptText('');

      if (triggerToast) {
        triggerToast("Estimasi pekerjaan berhasil dibuat dan dimasukkan ke dalam tabel!", "success");
      }

      if (onEstimationSuccess) {
        onEstimationSuccess(aiResult);
      }
    } catch (err) {
      clearInterval(timerInterval);
      clearInterval(triviaInterval);
      clearInterval(progressInterval);
      setIsProcessing(false);
      console.error("Gagal memproses estimasi:", err);
      setApiError(err.message || "Terjadi kesalahan saat memproses estimasi melalui server AI.");
    }
  };

  return (
    <>
      {/* 1. Base 2-Column Illustration Workspace Card (Matches Screenshot & Style) */}
      <div className="w-full bg-white rounded-xl md:rounded-2xl border border-slate-200/90 shadow-xs p-6 sm:p-8 md:p-10 my-3">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 relative">
          {/* Subtle vertical divider between columns on desktop */}
          <div className="hidden md:block absolute left-1/2 top-4 bottom-4 w-px bg-slate-100 -translate-x-1/2"></div>

          {/* Column 1: Upload Berkas DED */}
          <div className="flex flex-col items-center text-center p-4 sm:p-6 rounded-xl hover:bg-slate-50/40 transition-all">
            <div className="mb-4">
              <DedIllustration />
            </div>

            <h3 className="text-base sm:text-lg font-bold text-slate-900 mb-2 tracking-tight">
              Unggah Berkas Dokumen DED
            </h3>
            <p className="text-xs text-slate-500 leading-relaxed max-w-sm mb-6">
              Ekstrak kuantitas dan volume riil secara otomatis dari berkas gambar kerja 2D/3D Anda (PDF, DWG, DXF, IFC, RVT, SKP).
            </p>

            <button
              type="button"
              onClick={() => handleOpenModal('file')}
              className="mt-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-[#089613] hover:bg-[#06730e] text-white text-xs font-bold rounded-lg shadow-xs hover:shadow transition-all cursor-pointer select-none active:scale-98"
            >
              <span>+ Upload Berkas DED</span>
            </button>
          </div>

          {/* Column 2: Deteksi Berdasarkan Prompt AI */}
          <div className="flex flex-col items-center text-center p-4 sm:p-6 rounded-xl hover:bg-slate-50/40 transition-all">
            <div className="mb-4">
              <AiPromptIllustration />
            </div>

            <h3 className="text-base sm:text-lg font-bold text-slate-900 mb-2 tracking-tight">
              Deteksi Berdasarkan Prompt AI
            </h3>
            <p className="text-xs text-slate-500 leading-relaxed max-w-sm mb-6">
              Tuliskan konsep atau spesifikasi fisik bangunan yang ingin dibangun, dan biarkan AI menyusun rincian item pekerjaan standar AHSP.
            </p>

            <button
              type="button"
              onClick={() => handleOpenModal('prompt')}
              className="mt-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 hover:border-slate-400 text-xs font-bold rounded-lg shadow-2xs hover:shadow transition-all cursor-pointer select-none active:scale-98"
            >
              <span>+ Mulai dengan Prompt AI</span>
            </button>
          </div>
        </div>
      </div>

      {/* 2. Modal Estimasi (Exact Same Style as Project.jsx) */}
      {showModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full border border-slate-100 overflow-hidden relative animate-fadeIn max-h-[92vh] flex flex-col">

            {/* Modal Header */}
            <div className="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
              <h2 className="text-[14px] font-bold text-slate-800 uppercase tracking-wide">
                {inputMode === 'file' ? 'Upload Dokumen DED' : 'Deteksi Berdasarkan Prompt AI'}
              </h2>
              <button
                type="button"
                onClick={() => {
                  if (!isProcessing) setShowModal(false);
                }}
                className="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer text-base"
                disabled={isProcessing}
              >
                ✕
              </button>
            </div>

            {/* Modal Body: Processing State */}
            {isProcessing ? (
              <div className="p-6 flex flex-col min-h-[420px]">
                {/* Circle & Timer */}
                <div className="flex items-center gap-5 pb-5 border-b border-slate-100 mb-5">
                  <div className="relative w-20 h-20 flex-shrink-0 flex items-center justify-center">
                    <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                      <circle cx="50" cy="50" r="42" stroke="#f1f5f9" strokeWidth="6" fill="transparent" />
                      <circle
                        cx="50"
                        cy="50"
                        r="42"
                        stroke="#0fa83c"
                        strokeWidth="6"
                        fill="transparent"
                        strokeDasharray={2 * Math.PI * 42}
                        strokeDashoffset={2 * Math.PI * 42 * (1 - progressPercent / 100)}
                        strokeLinecap="round"
                        className="transition-all duration-300"
                      />
                    </svg>
                    <span className="absolute text-base font-extrabold text-emerald-950 tabular-nums">
                      {Math.round(progressPercent)}%
                    </span>
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-800 mb-0.5">
                      {inputMode === 'prompt' ? 'Memproses Deteksi Prompt AI' : 'Memproses Berkas Dokumen DED'}
                    </h3>
                    <p className="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                      <svg className="w-3.5 h-3.5 text-[#0fa83c] animate-pulse" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Waktu Berjalan: <span className="font-bold text-slate-700 tabular-nums">{formatTimer(elapsedTime)}</span>
                    </p>
                    {inputMode === 'prompt' ? (
                      <p className="text-[10.5px] text-slate-400 mt-1 font-semibold truncate max-w-[280px]">
                        Konsep: "{promptText.slice(0, 50)}..."
                      </p>
                    ) : file ? (
                      <p className="text-[10.5px] text-slate-400 mt-1 font-semibold truncate max-w-[280px]">
                        File: {file.name} ({(file.size / (1024 * 1024)).toFixed(2)} MB)
                      </p>
                    ) : null}
                  </div>
                </div>

                {/* Pipeline Stepper Checklist */}
                <div className="space-y-3 mb-5 flex-1">
                  {loadingSteps.map((step, idx) => {
                    const isCompleted = idx < activeStepIdx;
                    const isActive = idx === activeStepIdx;
                    return (
                      <div
                        key={idx}
                        className={`flex items-start gap-3 p-2.5 rounded-lg border transition-all ${isActive
                            ? 'bg-emerald-50/40 border-emerald-200 shadow-3xs'
                            : isCompleted
                              ? 'bg-slate-50/30 border-slate-100 opacity-80'
                              : 'border-transparent opacity-40'
                          }`}
                      >
                        <div className="flex-shrink-0 mt-0.5">
                          {isCompleted ? (
                            <div className="w-5 h-5 rounded-full bg-[#0fa83c] flex items-center justify-center text-white">
                              <svg className="w-3 h-3" fill="none" stroke="currentColor" strokeWidth="3" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                              </svg>
                            </div>
                          ) : isActive ? (
                            <div className="relative w-5 h-5 flex items-center justify-center">
                              <span className="animate-ping absolute inline-flex h-3.5 w-3.5 rounded-full bg-emerald-400 opacity-75"></span>
                              <div className="relative rounded-full h-4 w-4 border-2 border-[#0fa83c] border-t-transparent animate-spin"></div>
                            </div>
                          ) : (
                            <div className="w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center text-slate-400 text-[10px] font-bold">
                              {idx + 1}
                            </div>
                          )}
                        </div>
                        <div className="min-w-0">
                          <p className={`text-xs font-semibold ${isActive ? 'text-emerald-950 font-bold' : 'text-slate-650'}`}>
                            {step.label}
                          </p>
                          {isActive && (
                            <p className="text-[10px] text-emerald-700/80 font-medium animate-pulse mt-0.5">
                              Pekerjaan sedang berlangsung di server...
                            </p>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>

                {/* Trivia Box */}
                {TRIVIA_SLIDES[currentTriviaIdx] && (
                  <div className="bg-[#f0faf1] border border-[#daf2dd] rounded-xl p-3.5 relative overflow-hidden mt-1">
                    <h4 className="text-xs font-bold text-emerald-800 mb-0.5 flex items-center gap-1.5">
                      {TRIVIA_SLIDES[currentTriviaIdx].title}
                    </h4>
                    <p className="text-[11px] text-slate-600 leading-relaxed font-medium">
                      {TRIVIA_SLIDES[currentTriviaIdx].text}
                    </p>
                  </div>
                )}
              </div>
            ) : apiError ? (
              /* Modal Body: Error State */
              <div className="p-6 flex flex-col items-center">
                <div className="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-3 border border-red-100">
                  <Icons.AlertTriangle className="w-6 h-6" />
                </div>
                <div className="w-full border border-slate-100 rounded-xl p-4 bg-slate-50/60 mb-5 max-h-[250px] overflow-y-auto text-left">
                  <p className="text-xs font-bold text-red-800 mb-1">Terjadi Kesalahan:</p>
                  <p className="text-xs text-slate-650 leading-relaxed">{apiError}</p>
                </div>
                <div className="flex gap-3 w-full">
                  <button
                    type="button"
                    onClick={() => {
                      setApiError(null);
                      setShowModal(false);
                    }}
                    className="flex-1 border border-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg text-xs hover:bg-slate-50 cursor-pointer"
                  >
                    Tutup
                  </button>
                  <button
                    type="button"
                    onClick={handleSubmit}
                    className="flex-1 bg-[#0fa83c] hover:bg-[#0c8a31] text-white font-semibold py-2.5 rounded-lg text-xs cursor-pointer"
                  >
                    Coba Ulang
                  </button>
                </div>
              </div>
            ) : (
              /* Modal Body: Form Inputs (Exact same as Project.jsx) */
              <form onSubmit={handleSubmit} className="p-6 flex flex-col gap-4 overflow-y-auto">
                {inputMode === 'file' ? (
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-600">
                      File Dokumen DED (Detail Engineering Design)
                    </label>
                    <div
                      onClick={() => fileInputRef.current?.click()}
                      className="relative border-2 border-dashed border-slate-200 rounded-lg hover:border-[#0fa83c] transition-colors p-6 flex flex-col items-center justify-center cursor-pointer bg-slate-50/50"
                    >
                      <input
                        ref={fileInputRef}
                        type="file"
                        accept=".pdf,.dwg,.dxf,.dwt,.dwf,.dwfx,.svg,.plt,.hpgl,.hpg,.ifc,.rvt,.rfa,.nwd,.nwc,.skp,.jpeg,.png,.jpg"
                        onChange={(e) => {
                          if (e.target.files && e.target.files[0]) {
                            setFile(e.target.files[0]);
                          }
                        }}
                        className="hidden"
                      />
                      <svg className="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                      </svg>
                      <span className="text-xs font-semibold text-slate-600 mb-1">
                        {file ? file.name : 'Pilih file atau seret kemari'}
                      </span>
                      <span className="text-[10.5px] text-slate-400 text-center">
                        Mendukung format: PDF, DWG, DXF, IFC, RVT, SKP, JPEG, PNG, JPG (Maks. 500MB)
                      </span>
                    </div>
                  </div>
                ) : (
                  <div className="flex flex-col gap-2.5">
                    {/* Textarea Input */}
                    <div className="flex flex-col gap-1.5">
                      <label className="text-[12px] font-semibold text-slate-700 flex items-center justify-between">
                        <span>Deskripsi / Spesifikasi Bangunan</span>
                        <span className="text-[10.5px] text-slate-400 font-normal">
                          {promptText.length} karakter (Min. 15)
                        </span>
                      </label>
                      <textarea
                        rows="5"
                        required
                        placeholder="Tuliskan spesifikasi teknis atau gambaran bangunan...&#10;Contoh: Pembangunan rumah tinggal minimalis 2 lantai ukuran 8x15 meter. Lantai 1 terdapat carport, ruang tamu, ruang keluarga, 1 kamar tidur, dapur, dan kamar mandi..."
                        value={promptText}
                        onChange={(e) => setPromptText(e.target.value)}
                        className="bg-white border border-slate-250 rounded-lg p-3 text-xs text-slate-700 focus:outline-none focus:border-[#0fa83c] leading-relaxed"
                      />
                    </div>

                    {/* Rekomendasi Judul di Bawah Box Prompt */}
                    <div className="flex flex-col gap-1.5 pt-0.5">
                      <div className="flex items-center justify-between">
                        <span className="text-[11px] font-semibold text-slate-600">
                          Rekomendasi Contoh:
                        </span>
                        <span className="text-[10px] text-slate-400 font-normal">
                          Klik untuk isi otomatis
                        </span>
                      </div>

                      <div className="flex flex-wrap gap-1.5">
                        {PROMPT_RECOMMENDATIONS.map((item) => {
                          const isSelected = promptText === item.prompt;
                          return (
                            <button
                              key={item.id}
                              type="button"
                              onClick={() => setPromptText(item.prompt)}
                              className={`px-2.5 py-1 rounded-md text-[11px] font-medium transition-all cursor-pointer border flex items-center gap-1 select-none ${isSelected
                                  ? 'bg-[#0fa83c] text-white border-[#0fa83c] shadow-3xs'
                                  : 'bg-slate-50 text-slate-700 border-slate-250 hover:bg-slate-100 hover:border-slate-300'
                                }`}
                            >
                              <span>{item.title}</span>
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  </div>
                )}

                {/* Footer Buttons */}
                <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="px-4 py-2 border border-slate-200 rounded-lg text-slate-650 hover:bg-slate-50 text-xs font-semibold cursor-pointer"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={inputMode === 'file' ? !file : (!promptText || promptText.trim().length < 15)}
                    className={`px-4 py-2 rounded-lg text-xs font-semibold shadow-xs cursor-pointer flex items-center gap-1.5 ${(inputMode === 'file' ? file : (promptText && promptText.trim().length >= 15))
                        ? 'bg-[#0fa83c] hover:bg-[#0c8a31] text-white'
                        : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                      }`}
                  >
                    {inputMode === 'prompt' ? 'Deteksi Item Pekerjaan' : 'Proses Estimasi'}
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </>
  );
};

export default EstimationEmptyState;
