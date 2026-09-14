import React, { useState, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import Navbar from '../components/Navbar';
import proyekBg from '../assets/proyek-bg13.png';
import { createProject } from '../services/api';

const INDONESIA_LOCATIONS = [
  'Kab Simeulue',
  'Kab Cilacap',
  'Kota Jakarta Selatan',
  'Kota Jakarta Pusat',
  'Kota Jakarta Barat',
  'Kota Jakarta Timur',
  'Kota Jakarta Utara',
  'Kota Bandung',
  'Kota Surabaya',
  'Kota Semarang',
  'Kota Yogyakarta',
  'Kab Sleman',
  'Kab Bantul',
  'Kota Surakarta (Solo)',
  'Kota Malang',
  'Kota Denpasar',
  'Kab Badung',
  'Kota Medan',
  'Kota Palembang',
  'Kota Makassar',
  'Kota Balikpapan',
  'Kota Samarinda',
  'Kota Banjarmasin',
  'Kota Pontianak',
  'Kota Manado',
  'Kota Padang',
  'Kota Pekanbaru',
  'Kota Batam',
  'Kota Tangerang',
  'Kota Tangerang Selatan',
  'Kota Bekasi',
  'Kota Bogor',
  'Kota Depok',
  'Kab Bogor',
  'Kab Bekasi',
  'Lainnya...'
];

const BuatProyek = () => {
  const navigate = useNavigate();

  // Form State
  const [formData, setFormData] = useState({
    title: '',
    location: '',
    client: '',
    contractor_fee: '10,00',
    ppn: '11,00',
    summary: ''
  });

  // Photo state
  const [photoPreview, setPhotoPreview] = useState(null);
  const photoInputRef = useRef(null);

  // Documents state
  const [documents, setDocuments] = useState([]);
  const docInputRef = useRef(null);

  // Status & Toast
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toast, setToast] = useState({ show: false, message: '', type: 'success' });

  const triggerToast = (message, type = 'success') => {
    setToast({ show: true, message, type });
    setTimeout(() => {
      setToast(prev => ({ ...prev, show: false }));
    }, 3500);
  };

  // Photo handlers
  const handlePhotoClick = () => {
    if (photoInputRef.current) {
      photoInputRef.current.click();
    }
  };

  const handlePhotoChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        setPhotoPreview(reader.result);
      };
      reader.readAsDataURL(file);
      triggerToast('Foto proyek berhasil dipilih.');
    }
  };

  const handlePhotoReset = (e) => {
    e.stopPropagation();
    setPhotoPreview(null);
    if (photoInputRef.current) photoInputRef.current.value = '';
    triggerToast('Foto dikembalikan ke default.', 'info');
  };

  // Document handlers
  const handleDocClick = () => {
    if (docInputRef.current) {
      docInputRef.current.click();
    }
  };

  const handleDocChange = (e) => {
    const files = Array.from(e.target.files || []);
    if (files.length > 0) {
      const newDocs = files.map(f => ({
        id: `${Date.now()}-${Math.random().toString(36).substr(2, 5)}`,
        name: f.name,
        size: (f.size / (1024 * 1024)).toFixed(2) + ' MB',
        rawFile: f
      }));
      setDocuments(prev => [...prev, ...newDocs]);
      triggerToast(`${files.length} dokumen berhasil ditambahkan.`);
    }
  };

  const handleRemoveDoc = (docId) => {
    setDocuments(prev => prev.filter(d => d.id !== docId));
    triggerToast('Dokumen dihapus.', 'info');
  };

  // Submit Handler
  const handleSaveAndContinue = async (e) => {
    e.preventDefault();

    if (!formData.title.trim()) {
      triggerToast('Nama Proyek wajib diisi!', 'error');
      return;
    }
    if (!formData.location) {
      triggerToast('Lokasi Proyek wajib dipilih!', 'error');
      return;
    }
    if (!formData.client.trim()) {
      triggerToast('Pemilik Proyek wajib diisi!', 'error');
      return;
    }

    setIsSubmitting(true);
    try {
      const contractorFeeNum = parseFloat(String(formData.contractor_fee).replace(',', '.')) || 10;
      const ppnNum = parseFloat(String(formData.ppn).replace(',', '.')) || 11;

      const newProjectPayload = {
        title: formData.title.trim(),
        client: formData.client.trim(),
        location: formData.location,
        contractor_fee: contractorFeeNum,
        ppn: ppnNum,
        status: 'Tahap Estimasi',
        summary: formData.summary.trim() || 'Proyek Baru dibuat melalui formulir profil proyek.',
        image: photoPreview || '/assets/foto/proyek/no-foto.jpg'
      };

      const res = await createProject(newProjectPayload);
      const targetId = res?.uuid || res?.id || `proj-${Date.now()}`;

      triggerToast('Proyek baru berhasil dibuat!', 'success');
      setTimeout(() => {
        navigate(`/anggaran?id=${targetId}`);
      }, 700);
    } catch (err) {
      console.error('Gagal membuat proyek:', err);
      triggerToast(`Gagal membuat proyek: ${err.message}`, 'error');
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f7faf8] flex flex-col antialiased text-slate-800">
      {/* Top Navigation */}
      <Navbar />

      {/* Hero Banner: PROYEK BARU */}
      <div className="w-full relative overflow-hidden h-16 md:h-24 flex items-center justify-center select-none bg-[#84c225] shadow-xs">
        <img
          src={proyekBg}
          alt="Banner Proyek Baru"
          className="absolute inset-0 w-full h-full object-center pointer-events-none"
        />
        <div className="relative z-10 text-center px-4">
          <h1 className="text-xl sm:text-2xl md:text-3xl font-bold tracking-widest text-white uppercase drop-shadow-sm font-sans">
            PROYEK BARU
          </h1>
        </div>
      </div>

      {/* Main Content Workspace Card */}
      <main className="w-full max-w-[1360px] mx-auto px-4 sm:px-6 py-6 flex-1 flex flex-col">
        <div className="bg-white w-full rounded-md shadow-md border border-slate-200/90 overflow-hidden pb-12">
          {/* Header Subtitle Bar */}
          <div className="bg-[#f0f8ed] border-b border-emerald-150/70 py-3.5 px-4 text-center select-none">
            <h2 className="text-sm md:text-base font-bold text-slate-700 tracking-wider uppercase font-sans">
              LENGKAPI PROFIL <span className="text-slate-500 font-semibold">PROYEK</span>
            </h2>
          </div>

          {/* Form Container */}
          <form onSubmit={handleSaveAndContinue} className="p-6 md:p-10">
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
              
              {/* LEFT COLUMN: Foto Proyek, Tombol Tambah Dokumen, & Box Daftar Dokumen */}
              <div className="lg:col-span-4 flex flex-col items-center gap-4.5">
                {/* 1. Foto Proyek */}
                <div className="w-full max-w-[340px] aspect-square rounded-sm overflow-hidden border border-slate-200 shadow-3xs relative bg-[#9ece42] group select-none">
                  <img
                    src={photoPreview || "/assets/foto/proyek/no-foto.jpg"}
                    alt="Foto Proyek"
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.currentTarget.src = "/assets/foto/proyek/no-foto.jpg";
                    }}
                  />

                  {/* Floating Action Buttons: Trash (Reset) & Pencil (Upload) */}
                  <div className="absolute bottom-4 left-0 right-0 flex items-center justify-center gap-2.5 z-10">
                    <button
                      type="button"
                      onClick={handlePhotoReset}
                      className="w-8 h-8 rounded-full bg-[#1b5e20] hover:bg-red-600 text-white flex items-center justify-center shadow-md transition-transform hover:scale-110 cursor-pointer"
                      title="Hapus / Reset Foto"
                    >
                      <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>

                    <button
                      type="button"
                      onClick={handlePhotoClick}
                      className="w-8 h-8 rounded-full bg-[#1b5e20] hover:bg-[#0c8a31] text-white flex items-center justify-center shadow-md transition-transform hover:scale-110 cursor-pointer"
                      title="Ganti Foto Proyek"
                    >
                      <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2.2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                      </svg>
                    </button>
                  </div>

                  {/* Hidden File Input for Project Photo */}
                  <input
                    type="file"
                    ref={photoInputRef}
                    onChange={handlePhotoChange}
                    accept="image/*"
                    className="hidden"
                  />
                </div>

                {/* 2. Tombol TAMBAH DOKUMEN */}
                <div className="w-full max-w-[340px] flex justify-center">
                  <button
                    type="button"
                    onClick={handleDocClick}
                    className="inline-flex items-center gap-2 px-5 py-2 bg-[#7cb342] hover:bg-[#689f38] text-white text-[12px] font-bold uppercase rounded-sm shadow-3xs hover:shadow transition-all cursor-pointer select-none"
                  >
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clipRule="evenodd" />
                    </svg>
                    <span>TAMBAH DOKUMEN</span>
                  </button>

                  {/* Hidden File Input for Documents */}
                  <input
                    type="file"
                    ref={docInputRef}
                    onChange={handleDocChange}
                    multiple
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.dwg,.ifc,.rvt,image/*"
                    className="hidden"
                  />
                </div>

                {/* 3. Card Box Daftar Dokumen */}
                <div className="w-full max-w-[340px] border border-slate-200/90 rounded-sm overflow-hidden bg-white shadow-3xs">
                  <div className="bg-[#f0f8ed] border-b border-emerald-150/70 px-3.5 py-2">
                    <h3 className="text-xs font-bold text-slate-700">Daftar Dokumen</h3>
                  </div>

                  <div className="p-4 flex flex-col items-center justify-center min-h-[160px]">
                    {documents.length === 0 ? (
                      <div className="flex flex-col items-center justify-center text-center py-2">
                        <img
                          src="/assets/img/not-found.png"
                          alt="Tidak ada dokumen"
                          className="w-24 h-24 object-contain opacity-90"
                          onError={(e) => {
                            e.currentTarget.style.display = 'none';
                          }}
                        />
                        <span className="text-[12.5px] text-slate-500 font-medium mt-2">
                          Tidak ada dokumen
                        </span>
                      </div>
                    ) : (
                      <div className="w-full space-y-2">
                        {documents.map(doc => (
                          <div key={doc.id} className="flex items-center justify-between gap-2 p-2 bg-slate-50 border border-slate-200 rounded text-xs">
                            <div className="flex items-center gap-2 truncate">
                              <svg className="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clipRule="evenodd" />
                              </svg>
                              <div className="truncate">
                                <p className="font-semibold text-slate-700 truncate">{doc.name}</p>
                                <p className="text-[10.5px] text-slate-400">{doc.size}</p>
                              </div>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleRemoveDoc(doc.id)}
                              className="text-red-500 hover:text-red-700 text-xs font-bold p-1 cursor-pointer"
                              title="Hapus dokumen"
                            >
                              ✕
                            </button>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* RIGHT COLUMN: Form Fields */}
              <div className="lg:col-span-8 flex flex-col gap-4.5 text-xs text-slate-700">
                {/* 1. Nama Proyek */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">
                    Nama Proyek <span className="text-[11px] font-normal text-slate-400">(wajib diisi)</span>
                  </label>
                  <input
                    type="text"
                    value={formData.title}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    placeholder=""
                    className="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xs focus:outline-none focus:border-[#0fa83c] transition-colors text-slate-800"
                    required
                  />
                </div>

                {/* 2. Lokasi Proyek */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">
                    Lokasi Proyek <span className="text-[11px] font-normal text-slate-400">(wajib diisi)</span>
                  </label>
                  <div className="relative w-full">
                    <select
                      value={formData.location}
                      onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                      className="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xs focus:outline-none focus:border-[#0fa83c] transition-colors text-slate-800 appearance-none pr-8 cursor-pointer"
                      required
                    >
                      <option value="">Pilih Lokasi Proyek</option>
                      {INDONESIA_LOCATIONS.map(loc => (
                        <option key={loc} value={loc}>{loc}</option>
                      ))}
                    </select>
                    <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                      <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                    </div>
                  </div>
                </div>

                {/* 3. Pemilik Proyek */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">
                    Pemilik Proyek <span className="text-[11px] font-normal text-slate-400">(wajib diisi)</span>
                  </label>
                  <input
                    type="text"
                    value={formData.client}
                    onChange={(e) => setFormData({ ...formData, client: e.target.value })}
                    placeholder=""
                    className="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xs focus:outline-none focus:border-[#0fa83c] transition-colors text-slate-800"
                    required
                  />
                </div>

                {/* 4. Jasa Kontraktor */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">
                    Jasa Kontraktor <span className="text-[11px] font-normal text-slate-400">(wajib diisi)</span>
                  </label>
                  <div className="relative w-full">
                    <input
                      type="text"
                      value={formData.contractor_fee}
                      onChange={(e) => setFormData({ ...formData, contractor_fee: e.target.value })}
                      className="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xs focus:outline-none focus:border-[#0fa83c] transition-colors text-slate-800 text-right pr-8 font-medium"
                      required
                    />
                    <span className="absolute right-3 top-2 text-xs text-slate-500 pointer-events-none font-medium">%</span>
                  </div>
                </div>

                {/* 5. PPN */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">
                    PPN <span className="text-[11px] font-normal text-slate-400">(wajib diisi)</span>
                  </label>
                  <div className="relative w-full">
                    <input
                      type="text"
                      value={formData.ppn}
                      onChange={(e) => setFormData({ ...formData, ppn: e.target.value })}
                      className="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xs focus:outline-none focus:border-[#0fa83c] transition-colors text-slate-800 text-right pr-8 font-medium"
                      required
                    />
                    <span className="absolute right-3 top-2 text-xs text-slate-500 pointer-events-none font-medium">%</span>
                  </div>
                </div>

                {/* 6. Keterangan Lain */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-[12px] font-semibold text-slate-600">
                    Keterangan Lain
                  </label>
                  <textarea
                    rows={4}
                    value={formData.summary}
                    onChange={(e) => setFormData({ ...formData, summary: e.target.value })}
                    placeholder=""
                    className="w-full px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-xs focus:outline-none focus:border-[#0fa83c] transition-colors text-slate-800 resize-y"
                  ></textarea>
                </div>
              </div>
            </div>

            {/* BOTTOM ACTION BUTTONS: Centered */}
            <div className="flex items-center justify-center gap-2.5 mt-10 pt-6 border-t border-slate-150">
              {/* Button 1: SIMPAN & LANJUTKAN */}
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex items-center gap-1.5 px-6 py-2.5 bg-[#2e7d32] hover:bg-[#1b5e20] text-white text-[12px] font-bold uppercase rounded-sm shadow-xs hover:shadow transition-all cursor-pointer disabled:opacity-50"
              >
                {isSubmitting ? (
                  <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                ) : (
                  <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                )}
                <span>SIMPAN & LANJUTKAN</span>
              </button>

              {/* Button 2: BATAL */}
              <button
                type="button"
                onClick={() => navigate('/proyek')}
                className="inline-flex items-center gap-1.5 px-6 py-2.5 bg-[#fbc02d] hover:bg-[#f57f17] text-white text-[12px] font-bold uppercase rounded-sm shadow-xs hover:shadow transition-all cursor-pointer"
              >
                <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>BATAL</span>
              </button>
            </div>
          </form>
        </div>
      </main>

      {/* Toast Notification */}
      {toast.show && (
        <div className={`fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-lg shadow-lg text-white text-xs font-semibold flex items-center gap-2 transition-all ${
          toast.type === 'error' ? 'bg-red-600' : toast.type === 'info' ? 'bg-blue-600' : 'bg-[#0fa83c]'
        }`}>
          <span>{toast.message}</span>
        </div>
      )}
    </div>
  );
};

export default BuatProyek;
