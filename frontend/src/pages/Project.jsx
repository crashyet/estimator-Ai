import React, { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import Navbar from '../components/Navbar'

import { analyzeDED } from '../services/api'

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
  const [processingStep, setProcessingStep] = useState(0)
  const [apiError, setApiError] = useState(null)

  const steps = [
    'Mengunggah dokumen DED...',
    'Mengekstrak Bill of Quantities (BOQ)...',
    'Menghitung harga satuan bahan & upah...',
    'Menyusun Rencana Anggaran Biaya (RAB)...'
  ]

  const formatRupiah = (value) => {
    return "Rp " + value.toLocaleString('id-ID', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  // Handle Form Input
  const handleInputChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({ ...prev, [name]: value }))
  }

  const handleFileChange = (e) => {
    setFormData(prev => ({ ...prev, file: e.target.files[0] }))
  }



  // Handle Form Submit (Real API request)
  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!formData.file) {
      alert('Silakan pilih file DED terlebih dahulu!')
      return
    }

    setIsProcessing(true)
    setApiError(null)
    setProcessingStep(0)

    // Interval to cycle through visual loading step states
    const stepInterval = setInterval(() => {
      setProcessingStep(prev => (prev < steps.length - 1 ? prev + 1 : prev))
    }, 2000)

    try {
      const mappedData = await analyzeDED(formData.name, formData.client, formData.file)

      clearInterval(stepInterval)

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
      clearInterval(stepInterval)
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
          <div className="bg-white rounded-xl shadow-xl max-w-md w-full border border-slate-100 overflow-hidden relative">
            
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
              // Loading/Processing Screen
              <div className="p-8 flex flex-col items-center justify-center min-h-[300px]">
                <div className="relative w-16 h-16 mb-6">
                  {/* Spinner element */}
                  <div className="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                  <div className="absolute inset-0 rounded-full border-4 border-emerald-600 border-t-transparent animate-spin"></div>
                </div>
                
                <h3 className="text-sm font-bold text-slate-800 mb-2">Memproses File DED</h3>
                <p className="text-xs text-slate-500 text-center animate-pulse">{steps[processingStep]}</p>

                {/* Progress bar visual */}
                <div className="w-full max-w-xs h-1.5 bg-slate-50 border border-slate-100 rounded-full overflow-hidden mt-6">
                  <div 
                    className="h-full bg-emerald-600 transition-all duration-500 rounded-full"
                    style={{ width: `${((processingStep + 1) / steps.length) * 100}%` }}
                  ></div>
                </div>
              </div>
            ) : apiError ? (
              // Error state
              <div className="p-6 flex flex-col items-center text-center">
                <div className="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 mb-4 border border-red-100">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                  </svg>
                </div>
                <h3 className="text-sm font-bold text-slate-800 mb-2">Gagal Menghubungi API Backend</h3>
                <p className="text-xs text-red-650 max-w-xs mb-6 leading-relaxed">{apiError}</p>

                <div className="flex flex-col gap-2.5 w-full">
                  <button
                    onClick={() => setApiError(null)}
                    className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg text-xs transition-colors shadow-xs cursor-pointer"
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
                      accept=".xlsx,.xls,.pdf,.zip,.dwg,.dxf"
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
                      Mendukung format PDF, DWG, DXF AutoCAD (Maks. 10MB)
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