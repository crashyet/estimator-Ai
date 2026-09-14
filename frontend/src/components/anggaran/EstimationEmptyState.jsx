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

// Minimalist Line-art Illustration for Design / Blueprint option
const DesignCardIllustration = ({ isSelected }) => (
  <svg viewBox="0 0 120 80" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
    {/* Base Blueprint Sheet */}
    <rect
      x="12"
      y="10"
      width="96"
      height="60"
      rx="6"
      fill={isSelected ? "#f0faf1" : "#f8fafc"}
      stroke={isSelected ? "#089613" : "#94a3b8"}
      strokeWidth="1.8"
    />
    {/* Grid / Guide lines */}
    <line x1="12" y1="30" x2="108" y2="30" stroke={isSelected ? "#bbf7d0" : "#e2e8f0"} strokeWidth="1" strokeDasharray="2 2" />
    <line x1="12" y1="50" x2="108" y2="50" stroke={isSelected ? "#bbf7d0" : "#e2e8f0"} strokeWidth="1" strokeDasharray="2 2" />
    <line x1="45" y1="10" x2="45" y2="70" stroke={isSelected ? "#bbf7d0" : "#e2e8f0"} strokeWidth="1" strokeDasharray="2 2" />
    <line x1="75" y1="10" x2="75" y2="70" stroke={isSelected ? "#bbf7d0" : "#e2e8f0"} strokeWidth="1" strokeDasharray="2 2" />
    
    {/* 2D Plan Wall Layout */}
    <rect
      x="24"
      y="20"
      width="38"
      height="40"
      rx="2"
      fill="white"
      stroke={isSelected ? "#089613" : "#64748b"}
      strokeWidth="1.8"
    />
    <path
      d="M24 38H44V60"
      stroke={isSelected ? "#089613" : "#64748b"}
      strokeWidth="1.5"
    />
    {/* Door swing arc */}
    <path
      d="M38 38C38 32 44 28 50 28"
      stroke={isSelected ? "#16a34a" : "#94a3b8"}
      strokeWidth="1.2"
      strokeDasharray="2 2"
    />
    
    {/* 3D Isometric Element */}
    <path
      d="M78 26L94 18L102 23L86 31Z"
      fill={isSelected ? "#dcfce7" : "#f1f5f9"}
      stroke={isSelected ? "#089613" : "#64748b"}
      strokeWidth="1.5"
      strokeLinejoin="round"
    />
    <path
      d="M78 26V46L86 51V31Z"
      fill={isSelected ? "#bbf7d0" : "#e2e8f0"}
      stroke={isSelected ? "#089613" : "#64748b"}
      strokeWidth="1.5"
      strokeLinejoin="round"
    />
    <path
      d="M86 31L102 23V43L86 51Z"
      fill={isSelected ? "#86efac" : "#cbd5e1"}
      stroke={isSelected ? "#089613" : "#64748b"}
      strokeWidth="1.5"
      strokeLinejoin="round"
    />
  </svg>
);

// Minimalist Line-art Illustration for AI Prompt option
const PromptCardIllustration = ({ isSelected }) => (
  <svg viewBox="0 0 120 80" className="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
    {/* Base Document Sheet */}
    <rect
      x="16"
      y="10"
      width="88"
      height="60"
      rx="6"
      fill={isSelected ? "#f0faf1" : "#f8fafc"}
      stroke={isSelected ? "#089613" : "#94a3b8"}
      strokeWidth="1.8"
    />
    {/* Text Lines */}
    <rect x="28" y="22" width="34" height="4" rx="2" fill={isSelected ? "#089613" : "#64748b"} />
    <rect x="28" y="32" width="54" height="3" rx="1.5" fill={isSelected ? "#86efac" : "#cbd5e1"} />
    <rect x="28" y="40" width="46" height="3" rx="1.5" fill={isSelected ? "#86efac" : "#cbd5e1"} />
    <rect x="28" y="48" width="30" height="3" rx="1.5" fill={isSelected ? "#86efac" : "#cbd5e1"} />
    
    {/* Typing Cursor */}
    <line x1="61" y1="46" x2="61" y2="53" stroke={isSelected ? "#089613" : "#64748b"} strokeWidth="1.8" strokeLinecap="round" />

    {/* AI Sparkles */}
    <path
      d="M86 20L87.5 14L93.5 12.5L87.5 11L86 5L84.5 11L78.5 12.5L84.5 14Z"
      fill={isSelected ? "#089613" : "#64748b"}
    />
    <path
      d="M74 34L75 30L79 29L75 28L74 24L73 28L69 29L73 30Z"
      fill={isSelected ? "#16a34a" : "#94a3b8"}
    />
  </svg>
);

const EstimationEmptyState = ({
  projectId,
  projectDetail,
  onEstimationSuccess,
  triggerToast,
  onCancel
}) => {
  const [inputMode, setInputMode] = useState('file'); // 'file' (Berdasarkan Desain) | 'prompt'
  const [file, setFile] = useState(null);
  const [promptText, setPromptText] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [progressPercent, setProgressPercent] = useState(0);
  const [elapsedTime, setElapsedTime] = useState(0);
  const [activeStepIdx, setActiveStepIdx] = useState(0);
  const [currentTriviaIdx, setCurrentTriviaIdx] = useState(0);
  const [apiError, setApiError] = useState(null);
  const [isDragging, setIsDragging] = useState(false);

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

  // Drag & Drop handlers
  const handleDragOver = (e) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setIsDragging(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      setFile(e.dataTransfer.files[0]);
    }
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
    <div className="w-full max-w-3xl mx-auto bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden my-3">
      {/* 1. Processing State */}
      {isProcessing ? (
        <div className="p-6 md:p-8 flex flex-col min-h-[420px]">
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
              <h3 className="text-sm md:text-base font-bold text-slate-800 mb-0.5">
                {inputMode === 'prompt' ? 'Memproses Deteksi Prompt AI' : 'Memproses Berkas Dokumen DED'}
              </h3>
              <p className="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                <svg className="w-3.5 h-3.5 text-[#0fa83c] animate-pulse" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Waktu Berjalan: <span className="font-bold text-slate-700 tabular-nums">{formatTimer(elapsedTime)}</span>
              </p>
              {inputMode === 'prompt' ? (
                <p className="text-[11px] text-slate-400 mt-1 font-semibold truncate max-w-sm">
                  Konsep: "{promptText.slice(0, 60)}..."
                </p>
              ) : file ? (
                <p className="text-[11px] text-slate-400 mt-1 font-semibold truncate max-w-sm">
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
                  className={`flex items-start gap-3 p-3 rounded-lg border transition-all ${isActive
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
        /* 2. Error State */
        <div className="p-6 md:p-8 flex flex-col items-center">
          <div className="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-3 border border-red-100">
            <Icons.AlertTriangle className="w-6 h-6" />
          </div>
          <div className="w-full border border-slate-100 rounded-xl p-4 bg-slate-50/60 mb-5 max-h-[250px] overflow-y-auto text-left">
            <p className="text-xs font-bold text-red-800 mb-1">Terjadi Kesalahan:</p>
            <p className="text-xs text-slate-650 leading-relaxed">{apiError}</p>
          </div>
          <div className="flex gap-3 w-full max-w-xs">
            <button
              type="button"
              onClick={() => setApiError(null)}
              className="flex-1 border border-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg text-xs hover:bg-slate-50 cursor-pointer"
            >
              Kembali
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
        /* 3. Inline Form View */
        <form onSubmit={handleSubmit} className="p-6 md:p-8 flex flex-col gap-6">
          {/* Header Title & Subtitle */}
          <div className="text-center max-w-lg mx-auto">
            <h2 className="text-lg md:text-xl font-bold text-slate-900 tracking-tight">
              Pilih Metode Deteksi
            </h2>
            <p className="text-xs md:text-sm text-slate-500 mt-1 leading-relaxed">
              Pilih metode analisis yang ingin Anda gunakan untuk mendeteksi rincian anggaran proyek
            </p>
          </div>

          {/* 2 Method Option Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* Card 1: Berdasarkan Desain (Default) */}
            <div
              role="button"
              tabIndex={0}
              onClick={() => setInputMode('file')}
              onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') setInputMode('file'); }}
              className={`flex flex-col items-center justify-center p-5 rounded-xl border-2 transition-all cursor-pointer select-none text-center ${
                inputMode === 'file'
                  ? 'border-[#089613] bg-[#f0faf1] shadow-xs ring-2 ring-[#089613]/10'
                  : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
              }`}
            >
              <div className="w-full h-20 flex items-center justify-center mb-2.5 pointer-events-none">
                <DesignCardIllustration isSelected={inputMode === 'file'} />
              </div>
              <span className={`text-sm font-bold tracking-tight ${
                inputMode === 'file' ? 'text-emerald-800' : 'text-slate-800'
              }`}>
                Berdasarkan Desain
              </span>
              <span className={`text-xs mt-0.5 font-medium ${
                inputMode === 'file' ? 'text-emerald-600' : 'text-slate-400'
              }`}>
                File Dokumen DED / Gambar 2D & 3D
              </span>
            </div>

            {/* Card 2: Berdasarkan Prompt */}
            <div
              role="button"
              tabIndex={0}
              onClick={() => setInputMode('prompt')}
              onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') setInputMode('prompt'); }}
              className={`flex flex-col items-center justify-center p-5 rounded-xl border-2 transition-all cursor-pointer select-none text-center ${
                inputMode === 'prompt'
                  ? 'border-[#089613] bg-[#f0faf1] shadow-xs ring-2 ring-[#089613]/10'
                  : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
              }`}
            >
              <div className="w-full h-20 flex items-center justify-center mb-2.5 pointer-events-none">
                <PromptCardIllustration isSelected={inputMode === 'prompt'} />
              </div>
              <span className={`text-sm font-bold tracking-tight ${
                inputMode === 'prompt' ? 'text-emerald-800' : 'text-slate-800'
              }`}>
                Prompt
              </span>
              <span className={`text-xs mt-0.5 font-medium ${
                inputMode === 'prompt' ? 'text-emerald-600' : 'text-slate-400'
              }`}>
                Deskripsi & Spesifikasi Bangunan
              </span>
            </div>
          </div>

          {/* Sub-form: Upload or Textarea depending on selected card */}
          <div className="flex-1">
            {inputMode === 'file' ? (
              <div className="flex flex-col gap-1.5">
                <label className="text-xs font-semibold text-slate-700">
                  Unggah File Gambar / Dokumen Desain (DED)
                </label>
                <div
                  onClick={() => fileInputRef.current?.click()}
                  onDragOver={handleDragOver}
                  onDragLeave={handleDragLeave}
                  onDrop={handleDrop}
                  className={`relative border-2 border-dashed rounded-xl transition-all p-6 md:p-8 flex flex-col items-center justify-center cursor-pointer text-center ${
                    isDragging
                      ? 'border-[#089613] bg-[#f0faf1] ring-2 ring-[#089613]/20'
                      : file
                        ? 'border-emerald-500 bg-emerald-50/40'
                        : 'border-slate-250 hover:border-emerald-500 bg-slate-50/60 hover:bg-emerald-50/10'
                  }`}
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
                  {file ? (
                    <div className="flex flex-col items-center">
                      <div className="w-12 h-12 rounded-full bg-[#089613] text-white flex items-center justify-center mb-2.5 shadow-xs">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                      </div>
                      <span className="text-sm font-bold text-slate-800 break-all max-w-md mb-0.5">
                        {file.name}
                      </span>
                      <span className="text-xs text-emerald-700 font-semibold">
                        {(file.size / (1024 * 1024)).toFixed(2)} MB • Klik untuk ganti file
                      </span>
                    </div>
                  ) : (
                    <>
                      <div className="w-12 h-12 rounded-full bg-[#f0faf1] text-[#089613] border border-[#daf2dd] flex items-center justify-center mb-2.5">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                      </div>
                      <span className="text-sm font-bold text-slate-700 mb-1">
                        Pilih file dokumen DED atau seret kemari
                      </span>
                      <span className="text-xs text-slate-400 max-w-sm">
                        Mendukung format: PDF, DWG, DXF, IFC, RVT, SKP, JPEG, PNG (Maks. 500MB)
                      </span>
                    </>
                  )}
                </div>
              </div>
            ) : (
              <div className="flex flex-col gap-3">
                <div className="flex flex-col gap-1.5">
                  <label className="text-xs font-semibold text-slate-700 flex items-center justify-between">
                    <span>Deskripsi Konsep / Spesifikasi Bangunan</span>
                    <span className="text-[11px] text-slate-400 font-normal">
                      {promptText.length} karakter (Min. 15)
                    </span>
                  </label>
                  <textarea
                    rows="4"
                    placeholder="Tuliskan spesifikasi teknis atau gambaran bangunan...&#10;Contoh: Pembangunan rumah tinggal minimalis 2 lantai ukuran 8x15 meter. Lantai 1 terdapat carport, ruang tamu, ruang keluarga, 1 kamar tidur, dapur, dan kamar mandi..."
                    value={promptText}
                    onChange={(e) => setPromptText(e.target.value)}
                    className="w-full bg-slate-50/70 border border-slate-250 focus:bg-white rounded-xl p-3.5 text-xs text-slate-700 focus:outline-none focus:border-[#089613] focus:ring-1 focus:ring-[#089613] leading-relaxed transition-all resize-none"
                  />
                </div>

                {/* Prompt Recommendations */}
                <div className="flex flex-col gap-1.5">
                  <span className="text-xs font-semibold text-slate-600">
                    Contoh Cepat (Klik untuk isi otomatis):
                  </span>
                  <div className="flex flex-wrap gap-2">
                    {PROMPT_RECOMMENDATIONS.map((item) => (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => setPromptText(item.prompt)}
                        className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all cursor-pointer border ${
                          promptText === item.prompt
                            ? 'bg-[#089613] text-white border-[#089613] shadow-2xs font-semibold'
                            : 'bg-slate-50 text-slate-650 border-slate-250 hover:bg-emerald-50/50 hover:border-emerald-300 hover:text-emerald-900'
                        }`}
                      >
                        {item.title}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Action Button Footer */}
          <div className="pt-4 border-t border-slate-100 flex items-center justify-end gap-3 mt-2">
            {onCancel && (
              <button
                type="button"
                onClick={onCancel}
                className="px-5 py-2.5 border border-slate-250 hover:bg-slate-50 text-slate-650 text-xs font-semibold rounded-lg cursor-pointer transition-all"
              >
                Batal
              </button>
            )}
            <button
              type="submit"
              disabled={inputMode === 'file' ? !file : (!promptText || promptText.trim().length < 15)}
              className={`px-7 py-2.5 rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-2 ${
                (inputMode === 'file' ? file : (promptText && promptText.trim().length >= 15))
                  ? 'bg-[#089613] hover:bg-[#06730e] text-white cursor-pointer active:scale-98 shadow-sm hover:shadow'
                  : 'bg-slate-200 text-slate-400 cursor-not-allowed'
              }`}
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
              </svg>
              <span>Mulai Deteksi</span>
            </button>
          </div>
        </form>
      )}
    </div>
  );
};

export default EstimationEmptyState;
