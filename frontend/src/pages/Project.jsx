import { useState, useEffect, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import Navbar from '../components/Navbar'
import proyekBg from '../assets/proyek-bg13.png'

import {
  fetchProjects,
  createProject,
  deleteProject,
  updateProject,
  saveProjectEstimation,
  analyzeDED,
  analyzePrompt,
  mapToFrontendFormat
} from '../services/api'

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

  if (errStr.includes("prompt") || errStr.includes("domain") || errStr.includes("konstruksi") || errStr.includes("bangunan") || errStr.includes("karakter") || errStr.includes("singkat")) {
    return (
      <div className="text-left space-y-2.5 text-slate-700">
        <p className="font-bold text-amber-800 text-sm flex items-center gap-1.5">
          <span className="text-base">⚠️</span> Deskripsi / Prompt Tidak Sesuai
        </p>
        <div className="text-xs leading-relaxed text-slate-800 bg-amber-50 border border-amber-200 p-3 rounded-lg shadow-3xs">
          {rawError}
        </div>
        <div className="bg-slate-50 border border-slate-200 p-2.5 rounded-lg text-[11.5px] text-slate-600">
          <strong className="block text-slate-700 mb-0.5">💡 Contoh Prompt yang Benar:</strong>
          <em>"Pembangunan rumah tinggal minimalis 2 lantai ukuran 8x15 meter dengan 3 kamar tidur, struktur beton bertulang, dinding bata hebel, dan atap baja ringan genteng keramik."</em>
        </div>
      </div>
    );
  }

  if (errStr.includes("failed to fetch") || errStr.includes("network error") || errStr.includes("koneksi terputus") || errStr.includes("connection")) {
    return (
      <div className="text-left space-y-2 text-slate-700">
        <p className="font-bold text-red-700 text-sm">Koneksi Backend Terputus</p>
        <p className="text-xs leading-relaxed">
          Aplikasi tidak dapat menghubungi server backend API CodeIgniter 4 / FastAPI.
        </p>
        <p className="text-xs">
          <strong>Solusi:</strong> Pastikan Anda telah menjalankan backend CodeIgniter 4 (port 8080) dan FastAPI (port 8200) di terminal.
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

const getStatusStyle = (status) => {
  const norm = String(status || '').toLowerCase();
  if (norm.includes('selesai') || norm.includes('completed')) {
    return {
      statusColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      statusDot: 'bg-emerald-500'
    };
  }
  if (norm.includes('pengerjaan') || norm.includes('progress') || norm.includes('estimasi')) {
    return {
      statusColor: 'bg-amber-50 text-amber-700 border-amber-200',
      statusDot: 'bg-amber-500'
    };
  }
  return {
    statusColor: 'bg-blue-50 text-blue-700 border-blue-200',
    statusDot: 'bg-blue-500'
  };
};

const Project = () => {
  const navigate = useNavigate()

  // Project list state - purely from backend API / database
  const [projects, setProjects] = useState([])
  const [isLoadingProjects, setIsLoadingProjects] = useState(false)
  const [projectListError, setProjectListError] = useState(null)

  // Filters state
  const [filterName, setFilterName] = useState('')
  const [filterLocation, setFilterLocation] = useState('')
  const [filterYear, setFilterYear] = useState('')

  // Modal and Form States
  const [showModal, setShowModal] = useState(false)
  const [inputMode, setInputMode] = useState('file') // 'file' | 'prompt'
  const [formData, setFormData] = useState({
    name: '',
    client: '',
    location: '',
    contractor_fee: 10,
    ppn: 11,
    file: null,
    prompt: ''
  })

  // Edit Modal State
  const [showEditModal, setShowEditModal] = useState(false)
  const [editingProject, setEditingProject] = useState(null)
  const [editFormData, setEditFormData] = useState({
    title: '',
    client: '',
    location: '',
    contractor_fee: 10,
    ppn: 11,
    summary: '',
    status: 'Perencanaan'
  })

  // Team Collaboration Modal State
  const [teamModalProject, setTeamModalProject] = useState(null)

  // Processing States
  const [isProcessing, setIsProcessing] = useState(false)
  const [apiError, setApiError] = useState(null)

  // Custom Loading States
  const [elapsedTime, setElapsedTime] = useState(0)
  const [progressPercent, setProgressPercent] = useState(0)
  const [activeStepIdx, setActiveStepIdx] = useState(0)
  const [currentTriviaIdx, setCurrentTriviaIdx] = useState(0)
  const [loadingSteps, setLoadingSteps] = useState([])

  // Format seconds to MM:SS
  const formatTimer = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
  }

  // Fetch real projects from Backend API on mount
  const loadProjectsFromBackend = async () => {
    setIsLoadingProjects(true)
    setProjectListError(null)
    try {
      const data = await fetchProjects()
      if (Array.isArray(data) && data.length > 0) {
        const formatted = data.map((p) => {
          const styles = getStatusStyle(p.status)
          const createdDateStr = p.created_at ? p.created_at.split(' ')[0] : ''
          const yearStr = createdDateStr ? createdDateStr.slice(0, 4) : ''

          return {
            id: p.id,
            uuid: p.uuid,
            title: p.title || 'Proyek Tanpa Judul',
            client: p.client || '-',
            location: p.location || '-',
            contractor_fee: parseFloat(p.contractor_fee) || 10.0,
            ppn: parseFloat(p.ppn) || 11.0,
            budget: parseFloat(p.total_budget) || 0,
            status: p.status || 'Perencanaan',
            summary: p.summary || '',
            image: p.image || '/assets/foto/proyek/no-foto.jpg',
            latest_run: p.latest_run || null,
            statusColor: styles.statusColor,
            statusDot: styles.statusDot,
            date: createdDateStr,
            rawYear: yearStr,
            isLocked: false,
            link: `/anggaran?id=${p.uuid || p.id}`
          }
        })
        setProjects(formatted)
      } else {
        setProjects([])
      }
    } catch (err) {
      console.error("Gagal mengambil data proyek dari API:", err)
      setProjectListError(err.message || 'Gagal terhubung ke API backend.')
      setProjects([])
    } finally {
      setIsLoadingProjects(false)
    }
  }

  useEffect(() => {
    try {
      localStorage.removeItem('estimator_projects')
    } catch (_e) { }
    loadProjectsFromBackend()
  }, [])

  // Unique list of locations for dropdown filter
  const locationOptions = useMemo(() => {
    const locSet = new Set(['Kab Simeulue', 'Jakarta Selatan', 'Bandung', 'Surabaya'])
    projects.forEach(p => {
      if (p.location && p.location.trim()) {
        locSet.add(p.location.trim())
      }
    })
    return Array.from(locSet)
  }, [projects])

  // Filtered projects computed in real-time
  const filteredProjects = useMemo(() => {
    return projects.filter(p => {
      const matchName = !filterName.trim() || p.title.toLowerCase().includes(filterName.toLowerCase().trim())
      const matchLocation = !filterLocation || (p.location && p.location.toLowerCase() === filterLocation.toLowerCase())
      const matchYear = !filterYear.trim() || (p.date && p.date.includes(filterYear.trim())) || (p.rawYear && p.rawYear.includes(filterYear.trim()))
      return matchName && matchLocation && matchYear
    })
  }, [projects, filterName, filterLocation, filterYear])

  // Handle Form Input
  const handleInputChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({ ...prev, [name]: value }))
  }

  const handleFileChange = (e) => {
    setFormData(prev => ({ ...prev, file: e.target.files[0] }))
  }

  // Handle Project Delete
  const handleDeleteProject = async (p, e) => {
    if (e) {
      e.preventDefault()
      e.stopPropagation()
    }

    if (!window.confirm(`Apakah Anda yakin ingin menghapus proyek "${p.title}" beserta seluruh data WBS dan AHSP di dalamnya?`)) {
      return
    }

    try {
      await deleteProject(p.id)
    } catch (err) {
      console.warn("Could not delete from backend API:", err.message)
    }

    const updated = projects.filter(item => item.id !== p.id)
    setProjects(updated.length > 0 ? updated : DEFAULT_DEMO_PROJECTS)
  }

  // Toggle Lock
  const handleToggleLock = (projectId, e) => {
    if (e) {
      e.preventDefault()
      e.stopPropagation()
    }
    setProjects(prev => prev.map(p => {
      if (p.id === projectId) {
        return { ...p, isLocked: !p.isLocked }
      }
      return p
    }))
  }

  // Duplicate Project
  const handleDuplicateProject = (project, e) => {
    if (e) {
      e.preventDefault()
      e.stopPropagation()
    }
    const dupId = Date.now()
    const duplicated = {
      ...project,
      id: dupId,
      uuid: `dup-${dupId}`,
      title: `${project.title || 'Proyek'} (Salinan)`,
      date: new Date().toISOString().slice(0, 10),
      created_at: new Date().toISOString().slice(0, 10),
      link: `/anggaran?id=dup-${dupId}`
    }
    setProjects(prev => [duplicated, ...prev])
  }

  // Open Edit Modal
  const handleOpenEdit = (project, e) => {
    if (e) {
      e.preventDefault()
      e.stopPropagation()
    }
    setEditingProject(project)
    setEditFormData({
      title: project.title || '',
      client: project.client || '',
      location: project.location || '',
      contractor_fee: project.contractor_fee || 10,
      ppn: project.ppn || 11,
      summary: project.summary || '',
      status: project.status || 'Perencanaan'
    })
    setShowEditModal(true)
  }

  // Save Edit
  const handleSaveEdit = async (e) => {
    e.preventDefault()
    if (!editingProject) return

    try {
      await updateProject(editingProject.id, editFormData)
    } catch (err) {
      console.warn("Could not update project on backend:", err.message)
    }

    setProjects(prev => prev.map(p => {
      if (p.id === editingProject.id) {
        return {
          ...p,
          ...editFormData
        }
      }
      return p
    }))

    setShowEditModal(false)
    setEditingProject(null)
  }

  // Handle Form Submit (Real API request with simulated progress and trivia)
  const handleSubmit = async (e) => {
    e.preventDefault()

    if (inputMode === 'prompt') {
      if (!formData.prompt || !formData.prompt.trim()) {
        alert('Silakan tuliskan deskripsi/imajinasi rumah yang ingin dibuat!')
        return
      }
    } else {
      if (!formData.file) {
        alert('Silakan pilih file DED terlebih dahulu!')
        return
      }
    }

    let stepsList = []
    if (inputMode === 'prompt') {
      stepsList = [
        { label: 'Menganalisis konsep arsitektur & spesifikasi bangunan dari teks', duration: 4 },
        { label: 'Mengidentifikasi seluruh seksi WBS & item pekerjaan AHSP', duration: 8 },
        { label: 'Menetapkan satuan standar (m3/m2/m/unit) & inisialisasi volume 0.0', duration: 5 },
        { label: 'Pencarian semantik database AHSP & reranking kandidat terbaik', duration: 8 }
      ]
    } else {
      const file = formData.file
      const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase()

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
      } else {
        stepsList = [
          { label: 'Mengunggah gambar CAD/PDF ke server', duration: 5 },
          { label: 'Mengekstrak data teks, garis & dimensi vektor', duration: 15 },
          { label: 'Menyusun RAB WBS AHSP dengan Gemini AI', duration: 20 }
        ]
      }
    }

    setLoadingSteps(stepsList)
    setElapsedTime(0)
    setProgressPercent(0)
    setActiveStepIdx(0)
    setCurrentTriviaIdx(Math.floor(Math.random() * TRIVIA_SLIDES.length))
    setIsProcessing(true)
    setApiError(null)

    let elapsed = 0
    const timerId = setInterval(() => {
      elapsed += 1
      setElapsedTime(elapsed)

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

      const stepTargetPct = ((activeIdx + 1) / stepsList.length) * 98
      const prevStepPct = activeIdx > 0 ? (activeIdx / stepsList.length) * 98 : 0
      const stepStartTime = activeIdx > 0 ? stepsList.slice(0, activeIdx).reduce((sum, s) => sum + s.duration, 0) : 0
      const stepElapsed = elapsed - stepStartTime
      const stepDuration = stepsList[activeIdx].duration
      const stepProgressRatio = Math.min(stepElapsed / stepDuration, 0.95)

      const simulatedPct = prevStepPct + (stepTargetPct - prevStepPct) * stepProgressRatio
      setProgressPercent(Math.min(Math.round(simulatedPct), 98))
    }, 1000)

    const triviaId = setInterval(() => {
      setCurrentTriviaIdx(prev => (prev + 1) % TRIVIA_SLIDES.length)
    }, 12000)

    try {
      let projectId = Date.now()
      let projectUuid = null
      const defaultLoc = formData.location || 'Kab Simeulue'

      try {
        const summaryText = inputMode === 'prompt'
          ? `Estimasi konsep teks imajinasi: "${formData.prompt.slice(0, 90)}..."`
          : `Estimasi otomatis berkas DED: ${formData.file.name}`

        const created = await createProject({
          title: formData.name,
          client: formData.client,
          location: defaultLoc,
          contractor_fee: formData.contractor_fee || 10,
          ppn: formData.ppn || 11,
          status: 'Tahap Estimasi',
          summary: summaryText,
          image: '/assets/foto/proyek/no-foto.jpg'
        })
        if (created && created.id) {
          projectId = created.id
          projectUuid = created.uuid || null
        }
      } catch (err) {
        console.warn("Could not create project on backend database, using local id fallback:", err.message)
      }

      let aiResult
      if (inputMode === 'prompt') {
        if (!formData.prompt || formData.prompt.trim().length < 15) {
          clearInterval(timerId)
          clearInterval(triviaId)
          setIsProcessing(false)
          setApiError("Deskripsi konsep bangunan terlalu singkat (minimal 15 karakter). Mohon jelaskan konsep bangunan fisik atau pekerjaan renovasi yang ingin diestimasi.")
          return
        }
        aiResult = await analyzePrompt(formData.name, formData.client, formData.prompt)
      } else {
        aiResult = await analyzeDED(formData.name, formData.client, formData.file)
      }

      try {
        await saveProjectEstimation(projectUuid || projectId, aiResult)
      } catch (err) {
        console.warn("Could not save estimation to backend DB:", err.message)
      }

      clearInterval(timerId)
      clearInterval(triviaId)

      const mappedData = mapToFrontendFormat(formData.name, formData.client, aiResult)
      const styles = getStatusStyle('Tahap Estimasi')
      const projectUrlId = projectUuid || projectId
      const todayDateStr = new Date().toISOString().split('T')[0]

      const newProjectObj = {
        id: projectId,
        uuid: projectUuid,
        title: formData.name,
        client: formData.client,
        location: defaultLoc,
        contractor_fee: formData.contractor_fee || 10,
        ppn: formData.ppn || 11,
        budget: mappedData.project.budget || 0,
        status: 'Tahap Estimasi',
        statusColor: styles.statusColor,
        statusDot: styles.statusDot,
        date: todayDateStr,
        rawYear: todayDateStr.slice(0, 4),
        isLocked: false,
        image: '/assets/foto/proyek/no-foto.jpg',
        link: `/anggaran?id=${projectUrlId}`
      }

      const updatedProjects = [newProjectObj, ...projects.filter(p => p.id !== projectId)]
      setProjects(updatedProjects)

      setIsProcessing(false)
      setShowModal(false)
      setFormData({ name: '', client: '', location: '', file: null, prompt: '', contractor_fee: 10, ppn: 11 })
      navigate(`/anggaran?id=${projectUrlId}`)

    } catch (err) {
      clearInterval(timerId)
      clearInterval(triviaId)
      setIsProcessing(false)
      setApiError(err.message || 'Koneksi gagal terhubung ke backend API.')
    }
  }

  return (
    <div className="min-h-screen bg-[#fcfcfc] text-slate-800 font-sans flex flex-col antialiased">
      <Navbar />

      {/* 1. HERO BANNER: "DAFTAR PROYEK ANDA" WITH GREEN WAVY PATTERN */}
      <div className="w-full relative overflow-hidden h-10 md:h-28 flex items-center justify-center select-none bg-[#84c225] shadow-xs">
        <img
          src={proyekBg}
          alt="Daftar Proyek Anda"
          className="absolute inset-0 w-full h-full object-center pointer-events-none"
        />
        <div className="relative z-10 text-center px-4">
          <h1 className="text-xl sm:text-2xl md:text-3xl font-bold tracking-widest text-white uppercase drop-shadow-sm">
            DAFTAR PROYEK ANDA
          </h1>
        </div>
      </div>

      {/* 2. MAIN WRAPPER */}
      <div className="w-full max-w-[1360px] mx-auto px-4 sm:px-6 py-6 flex-1 flex flex-col">
        {/* 4. RED ACTION BUTTON: + BUAT PROYEK BARU */}
        <div className="mb-6 flex items-center justify-start">
          <button
            type="button"
            onClick={() => navigate('/buat_proyek')}
            className="bg-[#eb3324] hover:bg-[#d32f2f] text-white font-bold text-xs sm:text-[13px] uppercase tracking-wide px-5 py-2.5 rounded-full shadow-xs hover:shadow transition-all cursor-pointer flex items-center gap-2"
          >
            <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clipRule="evenodd" />
            </svg>
            BUAT PROYEK BARU
          </button>
        </div>

        <div className="bg-white w-full h-full shadow-md rounded-md pb-40">
          {/* 5. FILTER SECTION: ▼ Tampilkan Berdasarkan */}
          <div className="w-full mb-8">
            {/* Green Bar Header */}
            <div className="bg-[#0fa83c] rounded-t-sm px-4 py-2.5 flex items-center gap-2 text-white">
              <svg className="w-3 h-3 text-white fill-current" viewBox="0 0 20 20">
                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
              </svg>
              <span className="font-semibold text-xs sm:text-[13px] tracking-wide">
                Tampilkan Berdasarkan
              </span>
            </div>

            {/* Form Card Body */}
            <div className="bg-white p-4 shadow-md">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* Field 1: Nama Proyek */}
                <div>
                  <label className="text-[12px] text-slate-500 font-medium mb-1 block">
                    Nama Proyek
                  </label>
                  <input
                    type="text"
                    placeholder="Ketik Nama Proyek"
                    value={filterName}
                    onChange={(e) => setFilterName(e.target.value)}
                    className="w-full border border-slate-250 rounded-xs px-3 py-1.5 text-xs text-slate-750 placeholder-slate-400 focus:outline-none focus:border-[#0fa83c] transition-colors"
                  />
                </div>

                {/* Field 2: Lokasi Proyek */}
                <div>
                  <label className="text-[12px] text-slate-500 font-medium mb-1 block">
                    Lokasi Proyek
                  </label>
                  <select
                    value={filterLocation}
                    onChange={(e) => setFilterLocation(e.target.value)}
                    className="w-full border border-slate-250 rounded-xs px-3 py-1.5 text-xs text-slate-750 bg-white focus:outline-none focus:border-[#0fa83c] transition-colors cursor-pointer"
                  >
                    <option value="">Pilih Lokasi Proyek</option>
                    {locationOptions.map(loc => (
                      <option key={loc} value={loc}>{loc}</option>
                    ))}
                  </select>
                </div>

                {/* Field 3: Tahun */}
                <div>
                  <label className="text-[12px] text-slate-500 font-medium mb-1 block">
                    Tahun
                  </label>
                  <input
                    type="text"
                    placeholder="Ketik Tahun"
                    value={filterYear}
                    onChange={(e) => setFilterYear(e.target.value)}
                    className="w-full border border-slate-250 rounded-xs px-3 py-1.5 text-xs text-slate-750 placeholder-slate-400 focus:outline-none focus:border-[#0fa83c] transition-colors"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* 6. PROJECT CARDS GRID / STATES */}
          {isLoadingProjects && projects.length === 0 ? (
            <div className="py-20 text-center flex flex-col items-center justify-center gap-3">
              <div className="w-8 h-8 border-3 border-[#0fa83c] border-t-transparent rounded-full animate-spin"></div>
              <p className="text-xs font-semibold text-slate-600">Menghubungkan ke API backend dan memuat data proyek...</p>
            </div>
          ) : projects.length === 0 ? (
            <div className="py-16 text-center flex flex-col items-center justify-center border border-dashed border-slate-250 rounded-lg p-8 bg-white/70">
              <div className="w-12 h-12 rounded-full bg-emerald-50 text-[#0fa83c] flex items-center justify-center mb-3">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
              </div>
              <p className="text-sm font-bold text-slate-800 mb-1">Belum Ada Proyek di Database</p>
              <p className="text-xs text-slate-500 max-w-sm mb-4">
                Database backend belum memiliki data proyek. Silakan buat proyek baru untuk mulai menyusun estimasi anggaran biaya (RAB).
              </p>
              <button
                onClick={() => navigate('/buat_proyek')}
                className="inline-flex items-center gap-1.5 px-4 py-2 bg-[#d72638] hover:bg-[#b81d2d] text-white text-xs font-bold rounded-full shadow-3xs cursor-pointer transition-transform hover:scale-105"
              >
                + BUAT PROYEK BARU
              </button>
            </div>
          ) : filteredProjects.length === 0 ? (
            <div className="py-16 text-center flex flex-col items-center justify-center border border-dashed border-slate-250 rounded-lg p-8">
              <p className="text-sm font-semibold text-slate-600 mb-2">Tidak ada proyek yang sesuai dengan kriteria filter.</p>
              <button
                onClick={() => {
                  setFilterName('')
                  setFilterLocation('')
                  setFilterYear('')
                }}
                className="text-xs font-bold text-[#0fa83c] hover:underline cursor-pointer"
              >
                Reset Filter
              </button>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 px-4">
              {filteredProjects.map((p) => (
                <div
                  key={p.id}
                  className="bg-white border border-slate-200/90 rounded-none shadow-[0_2px_10px_rgba(0,0,0,0.06)] overflow-hidden hover:shadow-lg transition-shadow flex flex-col group pb-5"
                >
                  {/* Image Section: Green house outline illustration "Tidak ada foto" */}
                  <div
                    className="w-full h-44 sm:h-48 bg-[#9ece42] flex items-center justify-center overflow-hidden cursor-pointer"
                    onClick={() => navigate(p.link)}
                    title="Klik untuk melihat estimasi"
                  >
                    <img
                      src={p.image || "/assets/foto/proyek/no-foto.jpg"}
                      alt={p.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      onError={(e) => {
                        e.currentTarget.src = "/assets/foto/proyek/no-foto.jpg"
                      }}
                    />
                  </div>

                  {/* Project Title: Centered & Bold Gray */}
                  <div className="pt-4 pb-3 px-4 text-center">
                    <h3
                      onClick={() => navigate(p.link)}
                      className="font-bold text-[#555] text-[15px] uppercase tracking-wider truncate cursor-pointer hover:text-[#089613] transition-colors"
                      title={p.title}
                    >
                      {p.title}
                    </h3>
                  </div>

                  {/* Metadata Rows: 3 rows with square gray icon containers & dashed bottom borders */}
                  <div className="px-5 space-y-0 text-slate-500">
                    {/* Row 1: Client */}
                    <div className="flex items-center gap-3 py-1.5 border-b border-dashed border-slate-300">
                      <div className="w-6.5 h-6.5 bg-[#e4e7eb] flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                        </svg>
                      </div>
                      <span className="text-[13px] text-slate-500 truncate">{p.client || '-'}</span>
                    </div>

                    {/* Row 2: Location */}
                    <div className="flex items-center gap-3 py-1.5 border-b border-dashed border-slate-300">
                      <div className="w-6.5 h-6.5 bg-[#e4e7eb] flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                        </svg>
                      </div>
                      <span className="text-[13px] text-slate-500 truncate">{p.location || 'Kab Simeulue'}</span>
                    </div>

                    {/* Row 3: Date */}
                    <div className="flex items-center gap-3 py-1.5 border-b border-dashed border-slate-300">
                      <div className="w-6.5 h-6.5 bg-[#e4e7eb] flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                        </svg>
                      </div>
                      <span className="text-[13px] text-slate-500 truncate">{p.date || (p.created_at ? p.created_at.slice(0, 10) : '2026-09-07')}</span>
                    </div>
                  </div>

                  {/* Action Buttons Row: 5 Round Green Buttons */}
                  <div className="pt-5 px-3 flex items-center justify-center gap-2">
                    {/* Button 1: Edit (Pencil) */}
                    <button
                      type="button"
                      onClick={(e) => handleOpenEdit(p, e)}
                      className="w-9 h-9 rounded-full bg-[#089613] hover:bg-[#067a0f] text-white flex items-center justify-center transition-all hover:scale-105 shadow-xs cursor-pointer"
                      title="Edit Proyek"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                      </svg>
                    </button>

                    {/* Button 2: Team (Users) */}
                    <button
                      type="button"
                      onClick={(e) => {
                        e.preventDefault()
                        e.stopPropagation()
                        setTeamModalProject(p)
                      }}
                      className="w-9 h-9 rounded-full bg-[#089613] hover:bg-[#067a0f] text-white flex items-center justify-center transition-all hover:scale-105 shadow-xs cursor-pointer"
                      title="Tim Proyek"
                    >
                      <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 640 512">
                        <path d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm32 32h-64c-17.6 0-33.5 7.1-45.1 18.6 40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64zm-512 0c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4-11.7-11.5-27.6-18.6-45.1-18.6zm256-32c53 0 96-43 96-96s-43-96-96-96-96 43-96 96 43 96 96 96zm112 32h-16.4c-28.6 13.8-60.5 21.6-95.6 21.6s-67-7.8-95.6-21.6H176c-53 0-96 43-96 96v32c0 17.7 14.3 32 32 32h384c17.7 0 32-14.3 32-32v-32c0-53-43-96-96-96z" />
                      </svg>
                    </button>

                    {/* Button 3: Duplicate / Clone Documents */}
                    <button
                      type="button"
                      onClick={(e) => handleDuplicateProject(p, e)}
                      className="w-9 h-9 rounded-full bg-[#089613] hover:bg-[#067a0f] text-white flex items-center justify-center transition-all hover:scale-105 shadow-xs cursor-pointer"
                      title="Duplikat Proyek"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                        <rect x="8" y="7" width="11" height="13" rx="1.5" stroke="currentColor" strokeWidth="2" fill="none" />
                        <path d="M16 7V4.5A1.5 1.5 0 0014.5 3h-9A1.5 1.5 0 004 4.5v11A1.5 1.5 0 005.5 17H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      </svg>
                    </button>

                    {/* Button 4: Delete (Trash) */}
                    <button
                      type="button"
                      onClick={(e) => handleDeleteProject(p, e)}
                      className="w-9 h-9 rounded-full bg-[#089613] hover:bg-red-600 text-white flex items-center justify-center transition-all hover:scale-105 shadow-xs cursor-pointer"
                      title="Hapus Proyek"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>

                    {/* Button 5: Unlock / Lock */}
                    <button
                      type="button"
                      onClick={(e) => handleToggleLock(p.id, e)}
                      className="w-9 h-9 rounded-full bg-[#089613] hover:bg-[#067a0f] text-white flex items-center justify-center transition-all hover:scale-105 shadow-xs cursor-pointer"
                      title={p.isLocked ? "Proyek Terkunci (Klik untuk Buka)" : "Proyek Terbuka (Klik untuk Kunci)"}
                    >
                      {p.isLocked ? (
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                      ) : (
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 576 512">
                          <path d="M352 144c0-44.2 35.8-80 80-80s80 35.8 80 80v48c0 17.7 14.3 32 32 32s32-14.3 32-32V144C576 64.5 511.5 0 432 0S288 64.5 288 144v48H64c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V256c0-35.3-28.7-64-64-64H352V144z"/>
                        </svg>
                      )}
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* 7. MODAL: EDIT PROYEK */}
      {showEditModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full border border-slate-100 overflow-hidden">
            <div className="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
              <h2 className="text-sm font-bold text-slate-800 uppercase tracking-wide">Edit Data Proyek</h2>
              <button
                type="button"
                onClick={() => setShowEditModal(false)}
                className="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSaveEdit} className="p-6 flex flex-col gap-4">
              <div className="flex flex-col gap-1">
                <label className="text-xs font-semibold text-slate-600">Nama Proyek</label>
                <input
                  type="text"
                  required
                  value={editFormData.title}
                  onChange={(e) => setEditFormData(prev => ({ ...prev, title: e.target.value }))}
                  className="border border-slate-250 rounded-lg p-2 text-xs focus:outline-none focus:border-[#0fa83c]"
                />
              </div>

              <div className="flex flex-col gap-1">
                <label className="text-xs font-semibold text-slate-600">Klien / Pemilik</label>
                <input
                  type="text"
                  required
                  value={editFormData.client}
                  onChange={(e) => setEditFormData(prev => ({ ...prev, client: e.target.value }))}
                  className="border border-slate-250 rounded-lg p-2 text-xs focus:outline-none focus:border-[#0fa83c]"
                />
              </div>

              <div className="flex flex-col gap-1">
                <label className="text-xs font-semibold text-slate-600">Lokasi Proyek</label>
                <input
                  type="text"
                  placeholder="Contoh: Kab Simeulue, Jakarta"
                  value={editFormData.location}
                  onChange={(e) => setEditFormData(prev => ({ ...prev, location: e.target.value }))}
                  className="border border-slate-250 rounded-lg p-2 text-xs focus:outline-none focus:border-[#0fa83c]"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-semibold text-slate-600">Jasa Kontraktor (%)</label>
                  <input
                    type="number"
                    step="0.1"
                    value={editFormData.contractor_fee}
                    onChange={(e) => setEditFormData(prev => ({ ...prev, contractor_fee: e.target.value }))}
                    className="border border-slate-250 rounded-lg p-2 text-xs focus:outline-none focus:border-[#0fa83c]"
                  />
                </div>
                <div className="flex flex-col gap-1">
                  <label className="text-xs font-semibold text-slate-600">PPN (%)</label>
                  <input
                    type="number"
                    step="0.1"
                    value={editFormData.ppn}
                    onChange={(e) => setEditFormData(prev => ({ ...prev, ppn: e.target.value }))}
                    className="border border-slate-250 rounded-lg p-2 text-xs focus:outline-none focus:border-[#0fa83c]"
                  />
                </div>
              </div>

              <div className="flex flex-col gap-1">
                <label className="text-xs font-semibold text-slate-600">Keterangan / Ringkasan</label>
                <textarea
                  rows="3"
                  value={editFormData.summary}
                  onChange={(e) => setEditFormData(prev => ({ ...prev, summary: e.target.value }))}
                  className="border border-slate-250 rounded-lg p-2 text-xs focus:outline-none focus:border-[#0fa83c]"
                />
              </div>

              <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  className="px-4 py-1.5 border border-slate-250 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50 cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-[#0fa83c] hover:bg-[#0c8a31] text-white rounded-lg text-xs font-semibold cursor-pointer"
                >
                  Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 8. MODAL: TIM PROYEK */}
      {teamModalProject && (
        <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-sm w-full border border-slate-100 overflow-hidden">
            <div className="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
              <h2 className="text-xs font-bold text-slate-800 uppercase tracking-wide">
                Anggota Tim — {teamModalProject.title}
              </h2>
              <button
                type="button"
                onClick={() => setTeamModalProject(null)}
                className="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
              >
                ✕
              </button>
            </div>

            <div className="p-5 space-y-3">
              <div className="flex items-center gap-3 p-2.5 rounded-lg bg-emerald-50/50 border border-emerald-100">
                <div className="w-8 h-8 rounded-full bg-[#0fa83c] text-white flex items-center justify-center text-xs font-bold">
                  {teamModalProject.client ? teamModalProject.client.charAt(0).toUpperCase() : 'U'}
                </div>
                <div>
                  <p className="text-xs font-bold text-slate-800">{teamModalProject.client || 'User'}</p>
                  <p className="text-[10.5px] text-emerald-700 font-medium">Project Owner / Penanggung Jawab</p>
                </div>
              </div>

              <div className="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                <div className="w-8 h-8 rounded-full bg-slate-400 text-white flex items-center justify-center text-xs font-bold">
                  QS
                </div>
                <div>
                  <p className="text-xs font-bold text-slate-800">Estimator AI Senior</p>
                  <p className="text-[10.5px] text-slate-500 font-medium">Auto AHSP Engine & Auditor</p>
                </div>
              </div>
            </div>

            <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
              <button
                type="button"
                onClick={() => setTeamModalProject(null)}
                className="px-4 py-1.5 bg-[#0fa83c] hover:bg-[#0c8a31] text-white rounded-lg text-xs font-semibold cursor-pointer"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 9. MODAL: TAMBAH PROYEK BARU (+ BUAT PROYEK BARU) */}
      {showModal && (
        <div className="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full border border-slate-100 overflow-hidden relative">

            {/* Modal Header */}
            <div className="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
              <h2 className="text-[14px] font-bold text-slate-800 uppercase tracking-wide">Tambah Proyek Baru</h2>
              <button
                onClick={() => {
                  if (!isProcessing) setShowModal(false)
                }}
                className="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
                disabled={isProcessing}
              >
                ✕
              </button>
            </div>

            {/* Mode Switcher Tabs */}
            {!isProcessing && !apiError && (
              <div className="flex border-b border-slate-200 bg-slate-50/70 px-6 pt-2 gap-2">
                <button
                  type="button"
                  onClick={() => setInputMode('file')}
                  className={`pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
                    inputMode === 'file'
                      ? 'border-[#0fa83c] text-[#0fa83c]'
                      : 'border-transparent text-slate-500 hover:text-slate-700'
                  }`}
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                  </svg>
                  Upload Dokumen DED (File)
                </button>
                <button
                  type="button"
                  onClick={() => setInputMode('prompt')}
                  className={`pb-2.5 px-3 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-1.5 ${
                    inputMode === 'prompt'
                      ? 'border-[#0fa83c] text-[#0fa83c]'
                      : 'border-transparent text-slate-500 hover:text-slate-700'
                  }`}
                >
                  <svg className="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                  </svg>
                  Imajinasi Konsep AI (Prompt)
                  <span className="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.5 rounded-full font-bold">Baru</span>
                </button>
              </div>
            )}

            {/* Modal Body */}
            {isProcessing ? (
              <div className="p-6 flex flex-col min-h-[440px]">
                {/* Circle & Timer */}
                <div className="flex items-center gap-5 pb-5 border-b border-slate-100 mb-5">
                  <div className="relative w-20 h-20 flex-shrink-0 flex items-center justify-center">
                    <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                      <circle cx="50" cy="50" r="42" stroke="#f1f5f9" strokeWidth="6" fill="transparent" />
                      <circle cx="50" cy="50" r="42" stroke="#0fa83c" strokeWidth="6" fill="transparent"
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
                    <h3 className="text-sm font-bold text-slate-800 mb-0.5">
                      {inputMode === 'prompt' ? 'Memproses Konsep Imajinasi AI' : 'Memproses Model DED'}
                    </h3>
                    <p className="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                      <svg className="w-3.5 h-3.5 text-[#0fa83c] animate-pulse" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Waktu Berjalan: <span className="font-bold text-slate-700 tabular-nums">{formatTimer(elapsedTime)}</span>
                    </p>
                    {inputMode === 'prompt' ? (
                      <p className="text-[10px] text-slate-400 mt-1 font-semibold truncate max-w-[280px]">
                        Konsep: "{formData.prompt.slice(0, 50)}..."
                      </p>
                    ) : formData.file ? (
                      <p className="text-[10px] text-slate-400 mt-1 font-semibold truncate max-w-[280px]">
                        File: {formData.file.name} ({(formData.file.size / (1024 * 1024)).toFixed(2)} MB)
                      </p>
                    ) : null}
                  </div>
                </div>

                {/* Pipeline Stepper Checklist */}
                <div className="space-y-3 mb-6 flex-1">
                  {loadingSteps.map((step, idx) => {
                    const isCompleted = idx < activeStepIdx
                    const isActive = idx === activeStepIdx
                    return (
                      <div key={idx} className={`flex items-start gap-3 p-2.5 rounded-lg border transition-all ${isActive
                        ? 'bg-emerald-50/40 border-emerald-200 shadow-3xs'
                        : isCompleted
                          ? 'bg-slate-50/30 border-slate-100 opacity-80'
                          : 'border-transparent opacity-40'
                        }`}>
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
                    )
                  })}
                </div>

                {/* Trivia Box */}
                {TRIVIA_SLIDES[currentTriviaIdx] && (
                  <div className="bg-[#f0faf1] border border-[#daf2dd] rounded-xl p-4 relative overflow-hidden mt-2">
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
              <div className="p-6 flex flex-col items-center">
                <div className="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-4 border border-red-100">
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                  </svg>
                </div>
                <div className="w-full border border-slate-100 rounded-xl p-4.5 bg-slate-50/40 mb-6 max-h-[300px] overflow-y-auto">
                  {getFriendlyErrorMessage(apiError)}
                </div>
                <div className="flex gap-3 w-full">
                  <button
                    onClick={() => {
                      setApiError(null)
                      setShowModal(false)
                    }}
                    className="flex-1 border border-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg text-xs hover:bg-slate-50 cursor-pointer"
                  >
                    Tutup
                  </button>
                  <button
                    onClick={() => setApiError(null)}
                    className="flex-1 bg-[#0fa83c] hover:bg-[#0c8a31] text-white font-semibold py-2.5 rounded-lg text-xs cursor-pointer"
                  >
                    Coba Ulang
                  </button>
                </div>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="p-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">Nama Proyek</label>
                  <input
                    type="text"
                    name="name"
                    required
                    placeholder="Contoh: ASDAD"
                    value={formData.name}
                    onChange={handleInputChange}
                    className="bg-white border border-slate-250 rounded-lg p-2.5 text-xs text-slate-700 focus:outline-none focus:border-[#0fa83c]"
                  />
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">Nama Klien / Perusahaan</label>
                  <input
                    type="text"
                    name="client"
                    required
                    placeholder="Contoh: osdwd"
                    value={formData.client}
                    onChange={handleInputChange}
                    className="bg-white border border-slate-250 rounded-lg p-2.5 text-xs text-slate-700 focus:outline-none focus:border-[#0fa83c]"
                  />
                </div>

                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">Lokasi Proyek</label>
                  <input
                    type="text"
                    name="location"
                    placeholder="Contoh: Kab Simeulue"
                    value={formData.location}
                    onChange={handleInputChange}
                    className="bg-white border border-slate-250 rounded-lg p-2.5 text-xs text-slate-700 focus:outline-none focus:border-[#0fa83c]"
                  />
                </div>

                {inputMode === 'file' ? (
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-600">File Dokumen DED (Detail Engineering Design)</label>
                    <div className="relative border-2 border-dashed border-slate-200 rounded-lg hover:border-[#0fa83c] transition-colors p-6 flex flex-col items-center justify-center cursor-pointer bg-slate-50/50">
                      <input
                        type="file"
                        required
                        accept=".pdf,.dwg,.dxf,.dwt,.dwf,.dwfx,.svg,.plt,.hpgl,.hpg,.ifc,.rvt,.rfa,.nwd,.nwc,.skp,.jpeg,.png,.jpg"
                        onChange={handleFileChange}
                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                      />
                      <svg className="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                      </svg>
                      <span className="text-xs font-semibold text-slate-600 mb-1">
                        {formData.file ? formData.file.name : 'Pilih file atau seret kemari'}
                      </span>
                      <span className="text-[10.5px] text-slate-400 text-center">
                        Mendukung format: PDF, DWG, DXF, IFC, RVT, SKP, JPEG, PNG, JPG (Maks. 500MB)
                      </span>
                    </div>
                  </div>
                ) : (
                  <div className="flex flex-col gap-1.5">
                    <label className="text-[12px] font-semibold text-slate-600 flex items-center justify-between">
                      <span>Deskripsi Konsep / Imajinasi Rumah</span>
                      <span className="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Volume Wajib 0.0</span>
                    </label>
                    <textarea
                      name="prompt"
                      rows="4"
                      required
                      placeholder="Ceritakan gambaran rumah yang ingin Anda bangun...&#10;Contoh: Rumah minimalis modern 2 lantai ukuran 6x10 meter. Lantai 1 ada carport, ruang tamu, dapur. Lantai 2 ada 2 kamar tidur..."
                      value={formData.prompt}
                      onChange={handleInputChange}
                      className="bg-white border border-slate-250 rounded-lg p-3 text-xs text-slate-700 focus:outline-none focus:border-[#0fa83c]"
                    />
                  </div>
                )}

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
                    className="px-4 py-2 bg-[#0fa83c] hover:bg-[#0c8a31] text-white rounded-lg text-xs font-semibold shadow-xs cursor-pointer flex items-center gap-1.5"
                  >
                    {inputMode === 'prompt' ? 'Deteksi Item Pekerjaan' : 'Proses Estimasi'}
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