import React, { useState, useEffect, useRef } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import Navbar from '../components/Navbar'

import { analyzeDED } from '../services/api'

// Interactive Construction/QS Trivia Carousel Slides
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

// Parser to translate technical error logs into human-friendly explanations
const getFriendlyErrorMessage = (rawError) => {
  if (!rawError) return "Terjadi kesalahan tidak diketahui.";
  const errStr = String(rawError).toLowerCase();

  if (errStr.includes("autodesk") || errStr.includes("aps") || errStr.includes("translation") || errStr.includes("manifest") || errStr.includes("urn")) {
    return (
      <div className="text-left space-y-2 text-slate-700">
        <p className="font-bold text-red-700 text-sm">Gagal Mengonversi File Revit (.rvt)</p>
        <p className="text-xs leading-relaxed">
          Proses konversi file Revit (.rvt) ke format OpenBIM (IFC) via Autodesk Platform Services Cloud mengalami kegagalan, timeout, atau masalah hak akses. Hal ini biasanya terjadi pada file Revit berukuran besar, versi Revit tidak kompatibel, atau file corrupt.
        </p>
        <div className="bg-emerald-50 border border-emerald-150 p-3 rounded-lg text-xs text-emerald-950 mt-2.5">
          <strong className="block mb-1 text-emerald-800">💡 Solusi Alternatif Stabil:</strong>
          Silakan buka proyek Anda di software Autodesk Revit, lalu pilih <strong>File &rarr; Export &rarr; IFC</strong> untuk menyimpannya sebagai file <strong>.ifc</strong>.
          Kemudian, unggah file <strong>.ifc</strong> tersebut ke aplikasi ini. Pemrosesan file IFC dilakukan secara lokal di server kami tanpa cloud Autodesk, sehingga selesai dalam <strong>kurang dari 1 menit</strong> dan <strong>100% sukses</strong>!
        </div>
      </div>
    );
  }

  if (errStr.includes("413") || errStr.includes("too large") || errStr.includes("exceeds")) {
    return (
      <div className="text-left space-y-2 text-slate-700">
        <p className="font-bold text-red-700 text-sm">Ukuran File Terlalu Besar (Maks. 500MB)</p>
        <p className="text-xs leading-relaxed">
          File yang Anda unggah melebihi batas kapasitas server kami yang dikonfigurasi sebesar 500 megabyte.
        </p>
        <p className="text-xs">
          <strong>Solusi:</strong> Gunakan fitur <em>Purge Unused</em> di Revit atau CAD untuk menghapus komponen cadangan non-structural, atau bagi model Anda menjadi beberapa bagian kecil sebelum diunggah.
        </p>
      </div>
    );
  }

  if (errStr.includes("gemini") || errStr.includes("api key") || errStr.includes("generativelanguage") || errStr.includes("rate limit") || errStr.includes("limit")) {
    return (
      <div className="text-left space-y-2 text-slate-700">
        <p className="font-bold text-red-700 text-sm">Kuota Estimasi AI Habis / Kendala Gemini</p>
        <p className="text-xs leading-relaxed">
          Mesin AI pendukung (Google Gemini) tidak dapat dihubungi atau kunci API melampaui batas kecepatan permintaan (Rate Limit).
        </p>
        <p className="text-xs">
          <strong>Solusi:</strong> Tunggu 1 hingga 2 menit agar batas kuota di-reset otomatis oleh Google, kemudian klik tombol <strong>Coba Ulang</strong> di bawah.
        </p>
      </div>
    );
  }

  if (errStr.includes("failed to fetch") || errStr.includes("network error") || errStr.includes("koneksi terputus") || errStr.includes("connection")) {
    return (
      <div className="text-left space-y-2 text-slate-700">
        <p className="font-bold text-red-700 text-sm">Koneksi Backend Terputus</p>
        <p className="text-xs leading-relaxed">
          Aplikasi tidak dapat menghubungi server backend API Estimator di port 8200.
        </p>
        <p className="text-xs">
          <strong>Solusi:</strong> Pastikan Anda telah menjalankan program backend FastAPI di terminal menggunakan perintah <code>python3 main.py</code> di folder <code>api_v2</code> dan status server berjalan tanpa error.
        </p>
      </div>
    );
  }

  // Fallback error
  return (
    <div className="text-left space-y-2 text-slate-700">
      <p className="font-bold text-red-700 text-sm">Proses Estimasi Mengalami Masalah</p>
      <p className="text-xs leading-relaxed">Terjadi kesalahan tak terduga pada sistem parser kami saat membaca file DED Anda.</p>
      <div className="bg-slate-50 border border-slate-200 p-2.5 rounded font-mono text-[10.5px] text-slate-600 break-all whitespace-pre-wrap">
        Detail Teknis: {rawError}
      </div>
      <p className="text-[11px] text-slate-500">
        Saran: Pastikan file yang Anda unggah tidak rusak, tidak terkunci password, dan memiliki ekstensi yang valid (.pdf, .dwg, .dxf, .dwt, .dwf, .dwfx, .svg, .plt, .hpgl, .hpg, .ifc, .rvt, .rfa, .nwd, .nwc, .skp, .jpeg, .png, .jpg).
      </p>
    </div>
  );
};

const Project = () => {
  const navigate = useNavigate()

  // Initialize projects list from localStorage or defaults
  const [projects, setProjects] = useState(() => {
    const saved = localStorage.getItem('estimator_projects')
    if (saved) return JSON.parse(saved)
    return [
      {
        id: 1,
        title: 'Pembangunan Gedung Kantor Cabang',
        client: 'PT Beecons',
        budget: 995971307.58,
        status: 'Dalam Pengerjaan',
        statusColor: 'bg-amber-50 text-amber-700 border-amber-200',
        statusDot: 'bg-amber-500',
        date: '12 Mei 2026',
        link: '/anggaran'
      },
      {
        id: 2,
        title: 'Renovasi Laboratorium IT',
        client: 'Universitas Negeri',
        budget: 450000000.00,
        status: 'Selesai',
        statusColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        statusDot: 'bg-emerald-500',
        date: '28 Mar 2026',
        link: '/anggaran'
      },
      {
        id: 3,
        title: 'Pemasangan Jaringan Fiber Optic',
        client: 'Dinas Kominfo',
        budget: 350000000.00,
        status: 'Perencanaan',
        statusColor: 'bg-blue-50 text-blue-700 border-blue-200',
        statusDot: 'bg-blue-500',
        date: '01 Jun 2026',
        link: '/anggaran'
      },
      {
        id: 4,
        title: 'Pembangunan Gudang Logistik',
        client: 'PT Logistik Jaya',
        budget: 654028692.42,
        status: 'Selesai',
        statusColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        statusDot: 'bg-emerald-500',
        date: '10 Feb 2026',
        link: '/anggaran'
      }
    ]
  })

  // Modal and Form States
  const [showModal, setShowModal] = useState(false)
  const [formData, setFormData] = useState({
    name: '',
    client: '',
    file: null
  })

  // Processing States
  const [isProcessing, setIsProcessing] = useState(false)
  const [apiError, setApiError] = useState(null)

  // Custom Loading States
  const [elapsedTime, setElapsedTime] = useState(0)
  const [progressPercent, setProgressPercent] = useState(0)
  const [activeStepIdx, setActiveStepIdx] = useState(0)
  const [currentTriviaIdx, setCurrentTriviaIdx] = useState(0)
  const [loadingSteps, setLoadingSteps] = useState([])

  const formatRupiah = (value) => {
    return "Rp " + value.toLocaleString('id-ID', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  // Format seconds to MM:SS
  const formatTimer = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
  }

  // Handle Form Input
  const handleInputChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({ ...prev, [name]: value }))
  }

  const handleFileChange = (e) => {
    setFormData(prev => ({ ...prev, file: e.target.files[0] }))
  }

  // Handle Form Submit (Real API request with simulated progress and trivia)
  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!formData.file) {
      alert('Silakan pilih file DED terlebih dahulu!')
      return
    }

    const file = formData.file
    const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase()

    // Determine pipeline steps and duration targets based on file format
    let stepsList = []
    if (['.rvt', '.rfa', '.nwd', '.nwc', '.skp'].includes(ext)) {
      stepsList = [
        { label: `Mengunggah file 3D CAD/BIM (${ext.toUpperCase()}) ke server`, duration: 8 },
        { label: 'Otorisasi & Unggah ke S3 Autodesk Cloud', duration: 15 },
        { label: 'Pemrosesan 3D Model & Translasi Derivative (Cloud Polling)', duration: 180 },
        { label: 'Membaca komponen geometri & volume 3D', duration: 25 },
        { label: 'Menyusun RAB WBS AHSP dengan Gemini AI', duration: 30 }
      ]
    } else if (ext === '.ifc') {
      stepsList = [
        { label: 'Mengunggah file OpenBIM (.ifc) ke server', duration: 5 },
        { label: 'Membaca komponen geometri & volume 3D (Lokal)', duration: 12 },
        { label: 'Menyusun RAB WBS AHSP dengan Gemini AI', duration: 25 }
      ]
    } else if (['.jpeg', '.jpg', '.png'].includes(ext)) {
      stepsList = [
        { label: 'Mengunggah gambar DED (JPEG/PNG/JPG) ke server', duration: 5 },
        { label: 'Analisis visual geometri & Notasi Gambar dengan Gemini Vision', duration: 15 },
        { label: 'Menyusun RAB WBS AHSP dengan Gemini AI', duration: 20 }
      ]
    } else { // .dwg, .dxf, .pdf
      stepsList = [
        { label: 'Mengunggah gambar CAD/PDF ke server', duration: 5 },
        { label: 'Mengekstrak data teks, garis & dimensi vektor', duration: 15 },
        { label: 'Menyusun RAB WBS AHSP dengan Gemini AI', duration: 20 }
      ]
    }

    setLoadingSteps(stepsList)
    setElapsedTime(0)
    setProgressPercent(0)
    setActiveStepIdx(0)
    setCurrentTriviaIdx(Math.floor(Math.random() * TRIVIA_SLIDES.length))
    setIsProcessing(true)
    setApiError(null)

    // Timer & Progress simulator
    let elapsed = 0
    const totalExpectedTime = stepsList.reduce((sum, s) => sum + s.duration, 0)

    const timerId = setInterval(() => {
      elapsed += 1
      setElapsedTime(elapsed)

      // Calculate which step we are currently simulated to be on
      let cumulativeTime = 0
      let activeIdx = 0
      for (let i = 0; i < stepsList.length; i++) {
        cumulativeTime += stepsList[i].duration
        if (elapsed <= cumulativeTime) {
          activeIdx = i
          break
        }
        if (i === stepsList.length - 1) {
          activeIdx = stepsList.length - 1
        }
      }
      setActiveStepIdx(activeIdx)

      // Calculate percentage with an asymptotic curve approaching 98%
      // Ensures the progress bar doesn't get stuck at 100% while waiting for actual server response
      const stepTargetPct = ((activeIdx + 1) / stepsList.length) * 98
      const prevStepPct = activeIdx > 0 ? (activeIdx / stepsList.length) * 98 : 0

      const stepStartTime = activeIdx > 0 ? stepsList.slice(0, activeIdx).reduce((sum, s) => sum + s.duration, 0) : 0
      const stepElapsed = elapsed - stepStartTime
      const stepDuration = stepsList[activeIdx].duration
      const stepProgressRatio = Math.min(stepElapsed / stepDuration, 0.95) // Max 95% progress within a step simulation

      const simulatedPct = prevStepPct + (stepTargetPct - prevStepPct) * stepProgressRatio
      setProgressPercent(Math.min(Math.round(simulatedPct), 98))
    }, 1000)

    // Rotate construction trivia every 12 seconds
    const triviaId = setInterval(() => {
      setCurrentTriviaIdx(prev => (prev + 1) % TRIVIA_SLIDES.length)
    }, 12000)

    try {
      const mappedData = await analyzeDED(formData.name, formData.client, formData.file)

      clearInterval(timerId)
      clearInterval(triviaId)

      const projectId = Date.now();
      const mockNewProject = {
        id: projectId,
        title: mappedData.project.title || formData.name,
        client: mappedData.project.client || formData.client,
        budget: mappedData.project.budget,
        status: mappedData.project.status || 'Perencanaan',
        statusColor: 'bg-blue-50 text-blue-700 border-blue-200',
        statusDot: 'bg-blue-500',
        date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }),
        link: `/anggaran?id=${projectId}`
      }

      // Save projects
      const updatedProjects = [mockNewProject, ...projects]
      setProjects(updatedProjects)
      localStorage.setItem('estimator_projects', JSON.stringify(updatedProjects))

      // Save budget details
      localStorage.setItem(`estimator_uploaded_rows_${projectId}`, JSON.stringify(mappedData.anggaran))

      setIsProcessing(false)
      setShowModal(false)
      setFormData({ name: '', client: '', file: null })
      navigate(`/anggaran?id=${projectId}`)

    } catch (err) {
      clearInterval(timerId)
      clearInterval(triviaId)
      setIsProcessing(false)
      setApiError(err.message || 'Koneksi gagal terhubung ke backend API.')
    }
  }

  return (
    <div className="min-h-screen bg-[#f7faf8] pb-16 antialiased text-slate-800">
      <Navbar />

      {/* Main Title Section */}
      <div className="max-w-[1240px] mx-auto px-4 mt-6">
        <div className="w-full bg-[#f1faf2] border border-[#dff3e1] rounded-lg py-4 px-6 flex items-center justify-between shadow-xs">
          <h1 className="text-lg md:text-xl font-bold tracking-wider text-emerald-950 uppercase select-none">
            Daftar Proyek Aktif
          </h1>
          <button
            onClick={() => {
              setApiError(null)
              setShowModal(true)
            }}
            className="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all shadow-sm cursor-pointer flex items-center gap-1.5"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2.5" stroke="currentColor" className="w-4 h-4">
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Proyek
          </button>
        </div>
      </div>

      {/* Main Content Workspace */}
      <main className="max-w-[1240px] mx-auto px-4 mt-6">
        {/* Stats Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
          <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-3xs">
            <span className="text-[12.5px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Total Proyek</span>
            <span className="text-2xl font-bold text-slate-850">{projects.length} Proyek</span>
          </div>
          <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-3xs">
            <span className="text-[12.5px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Proyek Berjalan</span>
            <span className="text-2xl font-bold text-amber-500">
              {projects.filter(p => p.status === 'Dalam Pengerjaan').length} Proyek
            </span>
          </div>
          <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-3xs">
            <span className="text-[12.5px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Proyek Selesai</span>
            <span className="text-2xl font-bold text-emerald-500">
              {projects.filter(p => p.status === 'Selesai').length} Proyek
            </span>
          </div>
        </div>

        {/* Project Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {projects.map((p) => (
            <div key={p.id} className="bg-white border border-slate-100 rounded-xl shadow-3xs p-6 hover:shadow-2xs transition-all flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between mb-3.5">
                  <span className="text-[12.5px] font-medium text-slate-400">{p.date}</span>
                  <span className={`px-2.5 py-0.5 rounded-full text-[11px] font-bold border flex items-center gap-1.5 ${p.statusColor}`}>
                    <span className={`w-1.5 h-1.5 rounded-full ${p.statusDot}`}></span>
                    {p.status}
                  </span>
                </div>
                <h3 className="text-base font-bold text-slate-800 leading-snug mb-2 hover:text-emerald-700 transition-colors">
                  {p.title}
                </h3>
                <div className="flex items-center gap-1.5 mb-5 text-[13px] text-slate-500">
                  <span className="font-medium text-slate-600">{p.client}</span>
                </div>
              </div>

              <div className="pt-4 border-t border-slate-50 flex items-center justify-end mt-auto">
                <Link
                  to={p.link.includes('?id=') ? p.link : `/anggaran?id=${p.id}`}
                  className="bg-emerald-50 text-emerald-750 hover:bg-emerald-100 px-4 py-2 rounded-lg text-[12.5px] font-bold shadow-2xs transition-all flex items-center gap-1 cursor-pointer"
                >
                  Lihat Estimasi
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2.5" stroke="currentColor" className="w-3.5 h-3.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                  </svg>
                </Link>
              </div>
            </div>
          ))}
        </div>
      </main>

      {/* Add Project Modal */}
      {showModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full border border-slate-100 overflow-hidden relative">

            {/* Modal Header */}
            <div className="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
              <h2 className="text-[15px] font-bold text-slate-800 uppercase tracking-wide">Tambah Proyek Baru (DED)</h2>
              <button
                onClick={() => {
                  if (!isProcessing) setShowModal(false)
                }}
                className="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
                disabled={isProcessing}
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-5 h-5">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            {/* Modal Body */}
            {isProcessing ? (
              // Interactive Loading/Processing Screen
              <div className="p-6 flex flex-col min-h-[460px]">
                {/* Visual Circle & Timer */}
                <div className="flex items-center gap-5 pb-5 border-b border-slate-100 mb-5">
                  <div className="relative w-20 h-20 flex-shrink-0 flex items-center justify-center">
                    {/* Circle Background */}
                    <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                      <circle cx="50" cy="50" r="42" stroke="#f1f5f9" strokeWidth="6" fill="transparent" />
                      <circle cx="50" cy="50" r="42" stroke="#059669" strokeWidth="6" fill="transparent"
                        strokeDasharray={2 * Math.PI * 42}
                        strokeDashoffset={2 * Math.PI * 42 * (1 - progressPercent / 100)}
                        strokeLinecap="round"
                        className="transition-all duration-300"
                      />
                    </svg>
                    <span className="absolute text-base font-extrabold text-emerald-950 tabular-nums">
                      {progressPercent}%
                    </span>
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-800 mb-0.5">Memproses Model DED</h3>
                    <p className="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2.5" stroke="currentColor" className="w-3.5 h-3.5 text-emerald-600 animate-pulse">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Waktu Berjalan: <span className="font-bold text-slate-700 tabular-nums">{formatTimer(elapsedTime)}</span>
                    </p>
                    {formData.file && (
                      <p className="text-[10px] text-slate-400 mt-1 font-semibold truncate max-w-[280px]">
                        File: {formData.file.name} ({(formData.file.size / (1024 * 1024)).toFixed(2)} MB)
                      </p>
                    )}
                  </div>
                </div>

                {/* Pipeline Stepper Checklist */}
                <div className="space-y-3 mb-6 flex-1">
                  {loadingSteps.map((step, idx) => {
                    const isCompleted = idx < activeStepIdx
                    const isActive = idx === activeStepIdx
                    return (
                      <div key={idx} className={`flex items-start gap-3 p-2.5 rounded-lg border transition-all ${isActive
                        ? 'bg-emerald-50/40 border-emerald-200/85 shadow-2xs'
                        : isCompleted
                          ? 'bg-slate-50/30 border-slate-100 opacity-80'
                          : 'border-transparent opacity-40'
                        }`}>
                        <div className="flex-shrink-0 mt-0.5">
                          {isCompleted ? (
                            <div className="w-5 h-5 rounded-full bg-emerald-600 flex items-center justify-center text-white">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="3" stroke="currentColor" className="w-3.5 h-3.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                              </svg>
                            </div>
                          ) : isActive ? (
                            <div className="relative w-5 h-5 flex items-center justify-center">
                              <span className="animate-ping absolute inline-flex h-3.5 w-3.5 rounded-full bg-emerald-400 opacity-75"></span>
                              <div className="relative rounded-full h-4 w-4 border-2 border-emerald-650 border-t-transparent animate-spin"></div>
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
                    )
                  })}
                </div>

                {/* Construction/QS Trivia Box */}
                {TRIVIA_SLIDES[currentTriviaIdx] && (
                  <div className="bg-[#f0faf1] border border-[#daf2dd] rounded-xl p-4 animate-fade-in relative overflow-hidden mt-2">
                    <div className="absolute top-0 right-0 transform translate-x-4 -translate-y-4 opacity-10 text-emerald-900 pointer-events-none">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" className="w-24 h-24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                      </svg>
                    </div>
                    <h4 className="text-xs font-bold text-emerald-800 mb-1 flex items-center gap-1.5">
                      {TRIVIA_SLIDES[currentTriviaIdx].title}
                    </h4>
                    <p className="text-[11px] text-slate-600 leading-relaxed font-medium">
                      {TRIVIA_SLIDES[currentTriviaIdx].text}
                    </p>
                  </div>
                )}
              </div>
            ) : apiError ? (
              // Enhanced Friendly Error Screen
              <div className="p-6 flex flex-col items-center">
                <div className="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-4 border border-red-100 shadow-3xs">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                  </svg>
                </div>

                {/* Render the human-friendly parser message */}
                <div className="w-full border border-slate-100 rounded-xl p-4.5 bg-slate-50/40 mb-6 max-h-[300px] overflow-y-auto">
                  {getFriendlyErrorMessage(apiError)}
                </div>

                <div className="flex gap-3 w-full">
                  <button
                    onClick={() => {
                      setApiError(null)
                      setShowModal(false)
                    }}
                    className="flex-1 border border-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg text-xs transition-colors hover:bg-slate-50 cursor-pointer"
                  >
                    Tutup
                  </button>
                  <button
                    onClick={() => setApiError(null)}
                    className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg text-xs transition-colors shadow-xs cursor-pointer"
                  >
                    Coba Ulang
                  </button>
                </div>
              </div>
            ) : (
              // Form Input Screen
              <form onSubmit={handleSubmit} className="p-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12.5px] font-semibold text-slate-600">Nama Proyek</label>
                  <input
                    type="text"
                    name="name"
                    required
                    placeholder="Contoh: Pembangunan Kantor Cabang"
                    value={formData.name}
                    onChange={handleInputChange}
                    className="bg-white border border-slate-250 rounded-lg p-2.5 text-xs text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-3xs placeholder-slate-400 transition-colors"
                  />
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[12.5px] font-semibold text-slate-600">Nama Klien / Perusahaan</label>
                  <input
                    type="text"
                    name="client"
                    required
                    placeholder="Contoh: PT Beecons Nusantara"
                    value={formData.client}
                    onChange={handleInputChange}
                    className="bg-white border border-slate-250 rounded-lg p-2.5 text-xs text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-3xs placeholder-slate-400 transition-colors"
                  />
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[12.5px] font-semibold text-slate-600">File Dokumen DED (Detail Engineering Design)</label>
                  <div className="relative border-2 border-dashed border-slate-200 rounded-lg hover:border-emerald-500 transition-colors p-6 flex flex-col items-center justify-center cursor-pointer bg-slate-50/50">
                    <input
                      type="file"
                      required
                      accept=".pdf,.dwg,.dxf,.dwt,.dwf,.dwfx,.svg,.plt,.hpgl,.hpg,.ifc,.rvt,.rfa,.nwd,.nwc,.skp,.jpeg,.png,.jpg"
                      onChange={handleFileChange}
                      className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" className="w-8 h-8 text-slate-400 mb-2">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                    <span className="text-xs font-semibold text-slate-600 mb-1">
                      {formData.file ? formData.file.name : 'Pilih file atau seret kemari'}
                    </span>
                    <span className="text-[10.5px] text-slate-400">
                      Mendukung format 2D/3D & Gambar: PDF, DWG, DXF, DWT, DWF, DWFX, SVG, PLT, HPGL, IFC, RVT, RFA, NWD, NWC, SKP, JPEG, PNG, JPG (Maks. 500MB)
                    </span>
                  </div>
                </div>

                <div className="pt-3 border-t border-slate-50 flex items-center justify-end gap-2.5">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="px-4 py-2 border border-slate-200 rounded-lg text-slate-650 hover:bg-slate-50 text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
                  >
                    Proses Estimasi
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  )
}

export default Project