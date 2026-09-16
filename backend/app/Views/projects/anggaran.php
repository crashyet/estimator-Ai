<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Hasil Deteksi - <?= esc($project['title']) ?> | Estimator.id
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Hero Banner */
  .anggaran-banner {
    position: relative;
    width: 100%;
    background-color: #79bf39;
    background: linear-gradient(135deg, #74b836 0%, #88c946 50%, #68a82d 100%);
    position: relative;
    padding: 32px 20px 32px 20px;
    text-align: center;
    overflow: hidden;
    box-shadow: inset 0 -2px 6px rgba(0, 0, 0, 0.04);
  }
  .anggaran-banner-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-position: center right;
    pointer-events: none;
    opacity: 0.95;
  }
  .anggaran-banner-title {
    position: relative;
    z-index: 2;
    color: #ffffff;
    font-size: 30px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
  }

  /* Main Card Workspace */
  .anggaran-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
    padding: 28px 32px;
    margin: 0px auto 50px auto;
    position: relative;
    z-index: 5;
  }

  /* ------------------------------------------------------------- */
  /* PILIH METODE DETEKSI STYLES                                  */
  /* ------------------------------------------------------------- */
  .detect-selection-card {
    max-width: 840px;
    margin: 30px auto 50px auto;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 36px 40px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
    position: relative;
    z-index: 5;
  }
  .detect-method-option {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 24px 20px;
    text-align: center;
    cursor: pointer;
    background-color: #ffffff;
    transition: all 0.2s ease;
    user-select: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
  }
  .detect-method-option:hover {
    border-color: #cbd5e1;
    background-color: #f8fafc;
  }
  .detect-method-option.active {
    border-color: #089613;
    background-color: #f0faf1;
    box-shadow: 0 0 0 3px rgba(8, 150, 19, 0.12);
  }
  .detect-method-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-top: 10px;
    margin-bottom: 3px;
  }
  .detect-method-option.active .detect-method-title {
    color: #065f46;
  }
  .detect-method-desc {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 500;
  }
  .detect-method-option.active .detect-method-desc {
    color: #047857;
  }

  /* Dropzone */
  .ded-dropzone {
    border: 2px dashed #0fa83c;
    background-color: #f7fdf9;
    border-radius: 14px;
    padding: 38px 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .ded-dropzone:hover, .ded-dropzone.dragover {
    background-color: #ecfdf5;
    border-color: #059669;
    box-shadow: 0 0 0 4px rgba(15, 168, 60, 0.12);
  }
  .upload-icon-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background-color: #e6f7ec;
    color: #089613;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    border: 1px solid #bbf7d0;
  }

  /* Prompt Input & Chips */
  .prompt-textarea {
    width: 100%;
    background-color: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 13.5px;
    line-height: 1.6;
    color: #334155;
    resize: none;
    transition: all 0.15s ease;
  }
  .prompt-textarea:focus {
    outline: none;
    background-color: #ffffff;
    border-color: #089613;
    box-shadow: 0 0 0 3px rgba(8, 150, 19, 0.12);
  }
  .prompt-chip {
    background-color: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: 12.5px;
    font-weight: 500;
    padding: 7px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
  }
  .prompt-chip:hover {
    background-color: #ecfdf5;
    border-color: #6ee7b7;
    color: #065f46;
  }
  .prompt-chip.active {
    background-color: #089613;
    border-color: #089613;
    color: #ffffff;
    font-weight: 600;
  }

  /* Button Mulai Deteksi */
  .btn-mulai-deteksi {
    background-color: #089613;
    color: #ffffff;
    font-size: 14.5px;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    padding: 11px 32px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 4px 12px rgba(8, 150, 19, 0.28);
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .btn-mulai-deteksi:hover:not(:disabled) {
    background-color: #06730e;
    box-shadow: 0 6px 16px rgba(8, 150, 19, 0.38);
    transform: translateY(-1px);
    color: #ffffff;
  }
  .btn-mulai-deteksi:disabled {
    background-color: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
  }

  /* Processing Stepper */
  .processing-step-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid transparent;
    transition: all 0.2s ease;
  }
  .processing-step-item.active {
    background-color: #f0faf1;
    border-color: #bbf7d0;
  }
  .processing-step-item.completed {
    background-color: #f8fafc;
    border-color: #f1f5f9;
  }

  /* ------------------------------------------------------------- */
  /* WBS TABLE VIEW STYLES                                         */
  /* ------------------------------------------------------------- */
  .search-wrapper {
    position: relative;
    max-width: 480px;
    width: 100%;
  }
  .search-input {
    width: 100%;
    height: 46px;
    padding: 10px 42px 10px 42px;
    font-size: 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
    color: #334155;
    transition: all 0.15s ease;
  }
  .search-input:focus {
    outline: none;
    border-color: #0fa83c;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(15, 168, 60, 0.12);
  }
  .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 16px;
  }
  .search-clear-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 18px;
    cursor: pointer;
    padding: 2px 6px;
    line-height: 1;
  }
  .search-clear-btn:hover {
    color: #475569;
  }

  /* Table Design */
  .wbs-table-container {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background-color: #ffffff;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
    overflow: hidden;
  }
  .wbs-table-scroll-area {
    max-height: 600px;
    overflow-x: auto;
    overflow-y: auto;
    position: relative;
  }
  .wbs-table-scroll-area::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  .wbs-table-scroll-area::-webkit-scrollbar-track {
    background: #f8fafc;
  }
  .wbs-table-scroll-area::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
  .wbs-table-scroll-area::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }
  .wbs-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
    margin-bottom: 0;
  }
  .wbs-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
  }
  .wbs-table thead th {
    background-color: #089613;
    color: #ffffff;
    font-weight: 700;
    font-size: 13.5px;
    letter-spacing: 0.03em;
    padding: 14px 18px;
    border: none;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  /* Section Header Row */
  .wbs-section-row {
    background-color: #ffffff;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    transition: background-color 0.15s ease;
    cursor: pointer;
  }
  .wbs-section-row:hover {
    background-color: #f4faf4;
  }
  .wbs-section-row td {
    padding: 14px 18px !important;
  }
  .btn-toggle-section {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background-color: #d32f2f;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    line-height: 1;
    cursor: pointer;
    transition: transform 0.15s ease, background-color 0.15s ease;
    padding: 0;
    box-shadow: 0 2px 4px rgba(211, 47, 47, 0.25);
  }
  .btn-toggle-section:hover {
    background-color: #b71c1c;
    transform: scale(1.05);
  }
  .wbs-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    user-select: none;
  }

  /* Item Rows */
  .wbs-item-row {
    background-color: #fafcfa;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.15s ease;
  }
  .wbs-item-row:hover {
    background-color: #edf7ee;
  }
  .wbs-item-row td {
    padding: 13px 18px !important;
  }
  .wbs-item-no {
    color: #64748b;
    font-size: 13px;
    text-align: center;
    font-weight: 500;
  }
  .wbs-item-name {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    line-height: 1.5;
  }
  .badge-unmapped {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background-color: #fee2e2;
    border: 1px solid #f87171;
    color: #dc2626;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 800;
    margin-left: 8px;
    vertical-align: middle;
    cursor: help;
  }
  .wbs-item-volume {
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
    text-align: center;
    font-variant-numeric: tabular-nums;
  }
  .wbs-item-unit {
    font-size: 13.5px;
    color: #475569;
    text-align: center;
  }

  /* Action Buttons */
  .action-icon-btn {
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
  }
  .action-icon-book {
    color: #059669;
  }
  .action-icon-book:hover {
    color: #047857;
    background-color: #d1fae5;
  }
  .action-icon-trash {
    color: #ef4444;
  }
  .action-icon-trash:hover {
    color: #dc2626;
    background-color: #fee2e2;
  }
  .btn-petakan-modal {
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
    color: #047857;
    background-color: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }
  .btn-petakan-modal:hover {
    background-color: #d1fae5;
    color: #065f46;
    border-color: #6ee7b7;
  }

  /* Total Items Bar */
  .wbs-total-bar {
    background-color: #00802b;
    color: #ffffff;
    font-weight: 700;
    gap: 80px;
    font-size: 15px;
    padding: 13px 20px;
    display: flex;
    align-items: center;
    justify-content: end;
    letter-spacing: 0.02em;
    user-select: none;
  }

  /* Bottom Actions */
  .btn-lanjut-rab {
    background-color: #00802b;
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    padding: 11px 36px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 4px 12px rgba(0, 128, 43, 0.28);
  }
  .btn-lanjut-rab:hover {
    background-color: #006e24;
    box-shadow: 0 6px 16px rgba(0, 128, 43, 0.38);
    transform: translateY(-1px);
    color: #ffffff;
  }
  .btn-lanjut-rab:active {
    transform: translateY(0);
  }

  /* Backdrop Blur & Precision Card for Proceed RAB Modal */
  .modal-backdrop.show {
    background-color: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(6px) !important;
    -webkit-backdrop-filter: blur(6px) !important;
    opacity: 1 !important;
  }
  #proceedRabModal .modal-dialog {
    max-width: 512px !important;
    width: calc(100% - 2rem) !important;
    margin: 1.75rem auto;
  }
  #proceedRabModal .modal-content {
    background: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #f1f5f9 !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    overflow: hidden !important;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Banner with Green Wavy Pattern & Project Title -->
<div class="anggaran-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Estimator" class="anggaran-banner-bg" onerror="this.style.display='none'">
  <h1 class="anggaran-banner-title"><?= esc($project['title']) ?></h1>
</div>

<!-- Main Container -->
<main class="container-fluid px-3 px-md-5 my-4" style="max-width: 1440px;">

  <!-- ============================================================== -->
  <!-- 1. PILIH METODE DETEKSI CARD                                   -->
  <!-- ============================================================== -->
  <div id="detectionSelectionCard" class="detect-selection-card <?= $showDetect ? '' : 'd-none' ?>">

    <!-- Processing State (Initially Hidden) -->
    <div id="detectionProcessingState" class="d-none">
      <div class="d-flex align-items-center gap-4 pb-4 border-bottom border-light-subtle mb-4">
        <!-- Circular Progress Ring -->
        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; flex-shrink: 0;">
          <svg style="width: 100%; height: 100%; transform: rotate(-90deg);" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="42" stroke="#f1f5f9" stroke-width="8" fill="transparent" />
            <circle 
              id="progressCircleBar" 
              cx="50" cy="50" r="42" 
              stroke="#089613" 
              stroke-width="8" 
              fill="transparent" 
              stroke-dasharray="264" 
              stroke-dashoffset="264" 
              stroke-linecap="round" 
              style="transition: stroke-dashoffset 0.3s ease;" 
            />
          </svg>
          <span id="progressTextPercent" class="position-absolute fw-bolder text-dark" style="font-size: 17px;">
            0%
          </span>
        </div>
        <div>
          <h4 class="fw-bold text-dark fs-6 mb-1" id="processTitle">
            Memproses Analisis AI Estimator...
          </h4>
          <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="bi bi-clock-history text-success"></i>
            <span>Waktu Berjalan: <strong class="text-dark" id="elapsedTimerText">00:00</strong></span>
          </div>
          <div class="small text-secondary mt-1 font-medium" id="processDetailSubtitle">
            Sedang mengekstrak komponen dan struktur WBS proyek...
          </div>
        </div>
      </div>

      <!-- Stepper List -->
      <div class="d-flex flex-column gap-2 mb-4" id="stepperChecklist">
        <div class="processing-step-item" id="stepItem0">
          <div class="step-indicator me-1">
            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
          </div>
          <div class="small fw-semibold text-dark step-label">1. Memvalidasi Berkas & Informasi Bangunan</div>
        </div>
        <div class="processing-step-item text-muted opacity-50" id="stepItem1">
          <div class="step-indicator me-1"><i class="bi bi-circle"></i></div>
          <div class="small fw-semibold step-label">2. Menentukan Komponen Fisik & Struktur Utama</div>
        </div>
        <div class="processing-step-item text-muted opacity-50" id="stepItem2">
          <div class="step-indicator me-1"><i class="bi bi-circle"></i></div>
          <div class="small fw-semibold step-label">3. Menghasilkan Hierarki WBS Sesuai Standar</div>
        </div>
        <div class="processing-step-item text-muted opacity-50" id="stepItem3">
          <div class="step-indicator me-1"><i class="bi bi-circle"></i></div>
          <div class="small fw-semibold step-label">4. Memetakan ke Standar AHSP & Analisis Biaya</div>
        </div>
      </div>

      <!-- Rotating Trivia Box -->
      <div class="p-3 rounded-3 bg-light border border-success-subtle mb-2" style="background-color: #f0faf1 !important;">
        <div class="fw-bold text-success small mb-1" id="triviaTitle">
          💡 Tips Kecepatan Konversi
        </div>
        <div class="text-secondary small" id="triviaText" style="line-height: 1.5;">
          Sistem Estimasi menerapkan kebijakan 100% Real Data. AI tidak mengarang volume secara sembarang, melainkan memetakan kuantitas nyata dari model dan standar teknis.
        </div>
      </div>
    </div>

    <!-- Selection Form View -->
    <div id="detectionFormSection">
      <!-- Header Title & Subtitle -->
      <div class="text-center mb-4">
        <h2 class="fw-bold text-dark fs-4 mb-1">Pilih Metode Deteksi</h2>
        <p class="text-secondary small mb-0">
          Pilih metode analisis yang ingin Anda gunakan untuk mendeteksi rincian anggaran proyek
        </p>
      </div>

      <!-- 2 Method Selection Cards -->
      <div class="row g-3 mb-4">
        <!-- Option 1: Berdasarkan Desain -->
        <div class="col-12 col-md-6">
          <div 
            class="detect-method-option active" 
            id="methodOptionFile" 
            onclick="selectDetectMethod('file')"
          >
            <div style="height: 75px; width: 100%; display: flex; align-items: center; justify-content: center;">
              <svg viewBox="0 0 120 80" style="width: 100%; height: 75px;" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="12" y="10" width="96" height="60" rx="6" fill="#f0faf1" stroke="#089613" stroke-width="1.8" class="ded-card-bg" />
                <line x1="12" y1="30" x2="108" y2="30" stroke="#bbf7d0" stroke-width="1" stroke-dasharray="2 2" />
                <line x1="12" y1="50" x2="108" y2="50" stroke="#bbf7d0" stroke-width="1" stroke-dasharray="2 2" />
                <line x1="45" y1="10" x2="45" y2="70" stroke="#bbf7d0" stroke-width="1" stroke-dasharray="2 2" />
                <line x1="75" y1="10" x2="75" y2="70" stroke="#bbf7d0" stroke-width="1" stroke-dasharray="2 2" />
                <rect x="24" y="20" width="38" height="40" rx="2" fill="white" stroke="#089613" stroke-width="1.8" />
                <path d="M24 38H44V60" stroke="#089613" stroke-width="1.5" />
                <path d="M38 38C38 32 44 28 50 28" stroke="#16a34a" stroke-width="1.2" stroke-dasharray="2 2" />
                <path d="M78 26L94 18L102 23L86 31Z" fill="#dcfce7" stroke="#089613" stroke-width="1.5" stroke-linejoin="round" />
                <path d="M78 26V46L86 51V31Z" fill="#bbf7d0" stroke="#089613" stroke-width="1.5" stroke-linejoin="round" />
                <path d="M86 31L102 23V43L86 51Z" fill="#86efac" stroke="#089613" stroke-width="1.5" stroke-linejoin="round" />
              </svg>
            </div>
            <div class="detect-method-title">Berdasarkan Desain</div>
            <div class="detect-method-desc">File Dokumen DED / Gambar 2D & 3D</div>
          </div>
        </div>

        <!-- Option 2: Prompt -->
        <div class="col-12 col-md-6">
          <div 
            class="detect-method-option" 
            id="methodOptionPrompt" 
            onclick="selectDetectMethod('prompt')"
          >
            <div style="height: 75px; width: 100%; display: flex; align-items: center; justify-content: center;">
              <svg viewBox="0 0 120 80" style="width: 100%; height: 75px;" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="16" y="10" width="88" height="60" rx="6" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.8" class="prompt-card-bg" />
                <rect x="28" y="22" width="34" height="4" rx="2" fill="#64748b" class="prompt-card-accent1" />
                <rect x="28" y="32" width="54" height="3" rx="1.5" fill="#cbd5e1" class="prompt-card-accent2" />
                <rect x="28" y="40" width="46" height="3" rx="1.5" fill="#cbd5e1" class="prompt-card-accent2" />
                <rect x="28" y="48" width="30" height="3" rx="1.5" fill="#cbd5e1" class="prompt-card-accent2" />
                <line x1="61" y1="46" x2="61" y2="53" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" class="prompt-card-cursor" />
                <path d="M86 20L87.5 14L93.5 12.5L87.5 11L86 5L84.5 11L78.5 12.5L84.5 14Z" fill="#64748b" class="prompt-card-sparkle" />
                <path d="M74 34L75 30L79 29L75 28L74 24L73 28L69 29L73 30Z" fill="#94a3b8" class="prompt-card-sparkle2" />
              </svg>
            </div>
            <div class="detect-method-title">Prompt</div>
            <div class="detect-method-desc">Deskripsi & Spesifikasi Bangunan</div>
          </div>
        </div>
      </div>

      <!-- Subform: File Upload (DED) -->
      <div id="subformFile" class="mb-4">
        <label class="form-label small fw-bold text-dark mb-2">
          Unggah File Gambar / Dokumen Desain (DED)
        </label>
        
        <div 
          class="ded-dropzone" 
          id="dedDropzone" 
          onclick="document.getElementById('dedFileInput').click()"
          ondragover="handleDedDragOver(event)"
          ondragleave="handleDedDragLeave(event)"
          ondrop="handleDedDrop(event)"
        >
          <input 
            type="file" 
            id="dedFileInput" 
            class="d-none" 
            accept=".pdf,.dwg,.dxf,.dwt,.dwf,.dwfx,.svg,.plt,.hpgl,.hpg,.ifc,.rvt,.rfa,.nwd,.nwc,.skp,.jpeg,.png,.jpg"
            onchange="handleDedFileSelected(this.files)"
          >
          
          <div id="dropzoneEmptyState">
            <div class="upload-icon-circle">
              <i class="bi bi-cloud-arrow-up fs-3"></i>
            </div>
            <div class="fw-bold text-dark mb-1" style="font-size: 15px;">
              Pilih file dokumen DED atau seret kemari
            </div>
            <div class="text-muted small">
              Mendukung format: PDF, DWG, DXF, IFC, RVT, SKP, JPEG, PNG (Maks. 500MB)
            </div>
          </div>

          <div id="dropzoneSelectedState" class="d-none">
            <div class="upload-icon-circle" style="background-color: #089613; color: #ffffff; border-color: #089613;">
              <i class="bi bi-check-lg fs-3"></i>
            </div>
            <div class="fw-bold text-dark mb-1" id="selectedFileName" style="font-size: 15px;">
              nama_file.pdf
            </div>
            <div class="text-success small fw-semibold" id="selectedFileSize">
              0.00 MB • Klik untuk ganti file
            </div>
          </div>
        </div>
      </div>

      <!-- Subform: Prompt AI -->
      <div id="subformPrompt" class="mb-4 d-none">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label small fw-bold text-dark mb-0">
            Deskripsi Konsep / Spesifikasi Bangunan
          </label>
          <span class="text-muted small" id="promptCharCounter" style="font-size: 11.5px;">
            0 karakter (Min. 15)
          </span>
        </div>

        <textarea 
          id="promptTextInput" 
          rows="5" 
          class="prompt-textarea" 
          placeholder="Tuliskan spesifikasi teknis atau gambaran bangunan...&#10;Contoh: Pembangunan rumah tinggal minimalis 2 lantai ukuran 8x15 meter. Lantai 1 terdapat carport, ruang tamu, ruang keluarga, 1 kamar tidur, dapur, dan kamar mandi..."
          oninput="handlePromptInput(this.value)"
        ></textarea>

        <!-- Quick Examples (Chips) -->
        <div class="mt-3">
          <div class="small fw-bold text-secondary mb-2" style="font-size: 12.5px;">
            Contoh Cepat (Klik untuk isi otomatis):
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="prompt-chip" onclick="applyPromptPreset('rumah-2-lantai', this)">
              Rumah 2 Lantai (8x15 m)
            </button>
            <button type="button" class="prompt-chip" onclick="applyPromptPreset('rumah-1-lantai', this)">
              Rumah 1 Lantai (6x12 m)
            </button>
            <button type="button" class="prompt-chip" onclick="applyPromptPreset('ruko-2-lantai', this)">
              Ruko 2 Lantai (5x16 m)
            </button>
            <button type="button" class="prompt-chip" onclick="applyPromptPreset('renovasi-dapur', this)">
              Renovasi Dapur & Kamar Mandi
            </button>
          </div>
        </div>
      </div>

      <!-- Footer Action Buttons -->
      <div class="d-flex align-items-center justify-content-end gap-3 pt-3 border-top border-light-subtle">
        <?php if ($hasRuns): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm px-4 rounded-pill" onclick="cancelDetectMode()">
            Batal
          </button>
        <?php endif; ?>
        <button 
          type="button" 
          id="btnMulaiDeteksi" 
          class="btn-mulai-deteksi" 
          disabled 
          onclick="startDetectionProcess()"
        >
          <i class="bi bi-play-fill fs-5"></i>
          <span>Mulai Deteksi</span>
        </button>
      </div>
    </div>

  </div>

  <!-- ============================================================== -->
  <!-- 2. WBS TABLE VIEW                                              -->
  <!-- ============================================================== -->
  <div id="wbsTableContainerSection" class="anggaran-card <?= $showDetect ? 'd-none' : '' ?>">

    <!-- Action Toolbar: Search Filter -->
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4 pb-3 border-bottom border-light-subtle">
      <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input 
          type="text" 
          id="wbsSearchInput" 
          class="search-input" 
          placeholder="Cari pekerjaan, seksi, atau kode AHSP..." 
          oninput="handleSearch(this.value)"
        >
        <button 
          type="button" 
          id="clearSearchBtn" 
          class="search-clear-btn d-none" 
          onclick="clearSearch()" 
          title="Hapus pencarian"
        >&times;</button>
      </div>
    </div>

    <!-- WBS Table Wrapper -->
    <div class="wbs-table-container">
      <div class="wbs-table-scroll-area">
        <table class="wbs-table" id="wbsTable">
          <thead>
            <tr>
              <th style="width: 55px;" class="text-center">No.</th>
              <th>Uraian Pekerjaan</th>
              <th style="width: 140px;" class="text-center">Volume</th>
              <th style="width: 100px;" class="text-center">Satuan</th>
              <th style="width: 110px;" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody id="wbsTableBody">
            <?php if (empty($sections)): ?>
              <tr>
                <td colspan="5" class="py-5 text-center text-muted">
                  Belum ada data pekerjaan atau hasil deteksi untuk proyek ini.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($sections as $sIdx => $sec): ?>
                <!-- Section Group Row -->
                <tr class="wbs-section-row" data-section-code="<?= esc($sec['code']) ?>" onclick="toggleSection('<?= esc($sec['code']) ?>')">
                  <td class="text-center py-2 px-3 align-middle">
                    <button 
                      type="button" 
                      class="btn-toggle-section" 
                      id="btn-sec-<?= esc($sec['code']) ?>"
                      onclick="event.stopPropagation(); toggleSection('<?= esc($sec['code']) ?>')" 
                      title="Buka / Tutup Kategori"
                    >
                      <i class="bi bi-dash"></i>
                    </button>
                  </td>
                  <td class="py-2.5 px-3 align-middle wbs-section-title">
                    <?= esc($sec['name']) ?>
                  </td>
                  <td class="text-center align-middle"></td>
                  <td class="text-center align-middle"></td>
                  <td class="text-center align-middle"></td>
                </tr>

                <!-- Items in this Section -->
                <?php foreach ($sec['items'] as $iIdx => $item): ?>
                  <tr 
                    class="wbs-item-row" 
                    data-section-code="<?= esc($sec['code']) ?>"
                    data-item-id="<?= esc($item['id']) ?>"
                    data-item-name="<?= esc($item['name']) ?>"
                    data-item-volume="<?= esc($item['volume']) ?>"
                    data-item-unit="<?= esc($item['unit']) ?>"
                    data-ahsp-code="<?= esc($item['ahsp_code'] ?? '') ?>"
                    data-ahsp-name="<?= esc($item['ahsp_name'] ?? '') ?>"
                    data-ahsp-status="<?= esc($item['ahsp_status'] ?? 'mapped_high') ?>"
                    data-has-warning="<?= (($item['ahsp_status'] ?? '') === 'unmapped') ? '1' : '0' ?>"
                  >
                    <!-- No. -->
                    <td class="py-2.5 px-3 wbs-item-no align-middle">
                      <?= esc($item['no']) ?>
                    </td>

                    <!-- Uraian Pekerjaan -->
                    <td class="py-2.5 px-3 align-middle">
                      <div class="d-flex align-items-center">
                        <span class="wbs-item-name"><?= esc($item['ahsp_name']) ?></span>
                        <?php if (($item['ahsp_status'] ?? '') === 'unmapped'): ?>
                          <span class="badge-unmapped" title="<?= esc('Pekerjaan belum dipetakan ke standar AHSP') ?>">!</span>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Volume -->
                    <td class="py-2.5 px-3 wbs-item-volume align-middle" title="Klik untuk mengedit volume" onclick="openEditVolumeModal('<?= esc($item['id']) ?>')" style="cursor: pointer;">
                      <span class="volume-text"><?= number_format($item['volume'], 2, ',', '.') ?></span>
                    </td>

                    <!-- Satuan -->
                    <td class="py-2.5 px-3 wbs-item-unit align-middle">
                      <?= esc($item['unit']) ?>
                    </td>

                    <!-- Aksi -->
                    <td class="py-2.5 px-3 text-center align-middle">
                      <div class="d-inline-flex align-items-center justify-content-center gap-1">
                        <!-- Pemetaan AHSP Button -->
                        <button 
                          type="button" 
                          class="action-icon-btn action-icon-book" 
                          title="Pemetaan AHSP" 
                          onclick="openAhspModal('<?= esc($item['id']) ?>')"
                        >
                          <i class="bi bi-book"></i>
                        </button>

                        <!-- Delete Item Button -->
                        <button 
                          type="button" 
                          class="action-icon-btn action-icon-trash" 
                          title="Hapus Pekerjaan" 
                          onclick="confirmDeleteItem('<?= esc($item['id']) ?>', '<?= esc(addslashes($item['name'])) ?>')"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            <?php endif; ?>

            <!-- Empty search result row -->
            <tr id="emptySearchRow" class="d-none">
              <td colspan="5" class="py-4 text-center text-muted font-medium">
                Tidak ada pekerjaan yang cocok dengan pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Green Bottom Total Items Bar -->
      <div class="wbs-total-bar">
        <span>Total Item</span>
        <span id="totalItemCount" class="fw-bold"><?= $totalItems ?></span>
      </div>
    </div>

    <!-- Bottom Action Button: Lanjut ke RAB -->
    <div class="d-flex justify-content-end mt-4 pt-2">
      <button type="button" class="btn-lanjut-rab" onclick="handleLanjutKeRab()">
        Lanjut ke RAB
      </button>
    </div>

  </div>
</main>

<!-- Modal: Pemetaan AHSP Detail -->
<div class="modal fade" id="ahspDetailModal" tabindex="-1" aria-labelledby="ahspDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-success text-white py-3 px-4" style="background-color: #0fa83c !important;">
        <h5 class="modal-title fs-6 fw-bold d-flex align-items-center gap-2" id="ahspDetailModalLabel">
          <i class="bi bi-book"></i> Detail Pemetaan AHSP
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold mb-1">Uraian Pekerjaan</label>
          <div id="modalItemName" class="fw-bold text-dark fs-6"></div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label text-muted small fw-semibold mb-1">Volume</label>
            <div id="modalItemVolume" class="fw-semibold text-secondary"></div>
          </div>
          <div class="col-6">
            <label class="form-label text-muted small fw-semibold mb-1">Satuan</label>
            <div id="modalItemUnit" class="fw-semibold text-secondary"></div>
          </div>
        </div>

        <div class="p-3 rounded bg-light border border-secondary-subtle mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small fw-semibold">Kode AHSP Standar</span>
            <span id="modalAhspStatusBadge" class="badge bg-success-subtle text-success border border-success-subtle">Mapped</span>
          </div>
          <div id="modalAhspCode" class="fw-bold text-dark fs-6 mb-1"></div>
          <div id="modalAhspName" class="small text-secondary"></div>
        </div>

        <div id="modalWarningAlert" class="alert alert-warning py-2 px-3 small d-none" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>
          Pekerjaan ini belum terpetakan sempurna ke acuan AHSP standar. Anda dapat memetakannya secara manual.
        </div>
      </div>
      <div class="modal-footer bg-light px-4 py-3">
        <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-success btn-sm px-4 rounded-pill" onclick="saveAhspMapping()" style="background-color: #0fa83c; border-color: #0fa83c;">
          Simpan Pemetaan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Edit Volume Pekerjaan -->
<div class="modal fade" id="editVolumeModal" tabindex="-1" aria-labelledby="editVolumeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-dark text-white py-2.5 px-3">
        <h6 class="modal-title fs-6 fw-bold" id="editVolumeModalLabel">
          <i class="bi bi-pencil-square me-1"></i> Ubah Volume
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="editVolumeItemId">
        <div class="mb-2">
          <label class="form-label text-muted small mb-1 fw-medium" id="editVolumeItemTitle">-</label>
        </div>
        <div class="mb-3">
          <label for="editVolumeInput" class="form-label small fw-semibold mb-1">Volume</label>
          <div class="input-group input-group-sm">
            <input type="number" step="0.01" class="form-control" id="editVolumeInput" min="0">
            <span class="input-group-text" id="editVolumeUnitSpan">m2</span>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light p-2 justify-content-end">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="saveVolumeEdit()" style="background-color: #0fa83c; border-color: #0fa83c;">
          Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Konfirmasi Hapus Item -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-body p-4 text-center">
        <div class="w-12 h-12 rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
          <i class="bi bi-trash fs-3"></i>
        </div>
        <h6 class="fw-bold mb-2">Hapus Pekerjaan Ini?</h6>
        <p class="text-secondary small mb-4" id="deleteItemModalName">Apakah Anda yakin ingin menghapus pekerjaan ini dari estimasi?</p>
        <input type="hidden" id="deleteTargetItemId">
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-light btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger btn-sm px-3 rounded-pill" onclick="executeDeleteItem()">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Konfirmasi Lanjut ke RAB (Warning jika ada unmapped items) -->
<div class="modal fade" id="proceedRabModal" tabindex="-1" aria-labelledby="proceedRabModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <!-- KASUS 1: Masih ada item yang belum dipetakan (UNMAPPED) -->
      <div id="proceedUnmappedContent">
        <!-- Header Warning Amber -->
        <div class="d-flex align-items-center justify-content-between" style="background-color: #d97706; color: #ffffff; padding: 14px 20px;">
          <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; color: #fef3c7;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <h3 class="mb-0 fw-bold text-uppercase" style="font-size: 13.5px; letter-spacing: 0.04em; color: #ffffff;">
              PERHATIAN: ITEM BELUM TERPETAKAN
            </h3>
          </div>
          <button type="button" class="rounded-circle border-0 d-flex align-items-center justify-content-center text-white" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; width: 28px; height: 28px; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='rgba(0,0,0,0.15)'" onmouseout="this.style.background='transparent'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width: 16px; height: 16px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
          <!-- Alert Banner Box -->
          <div style="background-color: #fffbeb; border: 1px solid rgba(253, 230, 138, 0.8); border-radius: 12px; padding: 14px 16px; display: flex; gap: 12px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" 
                 style="width: 28px; height: 28px; background-color: #fef3c7; color: #b45309; font-size: 14px;">
              !
            </div>
            <div style="font-size: 12.5px; line-height: 1.5;">
              <p class="fw-bold mb-0.5" style="color: #451a03; font-size: 12.5px;">
                Ada <span id="proceedWarningCount">0</span> pekerjaan yang belum dipetakan ke AHSP
              </p>
              <p class="mb-0" style="color: #92400e; font-size: 12px; margin-top: 2px;">
                Item yang belum dipetakan tidak memiliki kode dan acuan harga satuan standar. Disarankan untuk memetakan seluruh pekerjaan terlebih dahulu.
              </p>
            </div>
          </div>

          <!-- Section Heading -->
          <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-bold text-uppercase" style="font-size: 11.5px; color: #475569; letter-spacing: 0.05em;">
                DAFTAR PEKERJAAN BELUM DIPETAKAN (<span id="proceedListCount">0</span>):
              </span>
              <span style="font-size: 11px; color: #94a3b8;">
                Klik tombol untuk memetakan
              </span>
            </div>

            <!-- Scrollable Unmapped Items List -->
            <div class="rounded-3 border overflow-auto" style="max-height: 208px; background-color: rgba(248, 250, 252, 0.6); border-color: #e2e8f0 !important;" id="unmappedItemsList">
              <!-- Populated by JS -->
            </div>
          </div>

          <!-- Confirmation Prompt Box -->
          <div class="text-center rounded-3 border" style="background-color: #f8fafc; border-color: rgba(226, 232, 240, 0.7) !important; padding: 12px;">
            <p class="mb-0 fw-semibold" style="font-size: 12.5px; color: #334155;">
              Apakah Anda yakin ingin tetap melanjutkan ke halaman RAB?
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="pt-2 d-flex align-items-center justify-content-end gap-2" style="border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn btn-sm fw-semibold" data-bs-dismiss="modal" style="font-size: 12px; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; background-color: #ffffff; padding: 8px 16px; cursor: pointer;">
              Periksa & Petakan Dulu
            </button>
            <button type="button" class="btn btn-sm fw-bold text-white d-inline-flex align-items-center gap-1.5" onclick="confirmProceedToRab()" style="background-color: #d97706; border: none; border-radius: 8px; font-size: 12px; padding: 8px 18px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); cursor: pointer;" onmouseover="this.style.backgroundColor='#b45309'" onmouseout="this.style.backgroundColor='#d97706'">
              <span>Tetap Lanjut ke RAB</span>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- KASUS 2: Semua Item Terpetakan (SIAP) -->
      <div id="proceedReadyContent" class="d-none">
        <!-- Header Green -->
        <div class="d-flex align-items-center justify-content-between" style="background-color: #00802b; color: #ffffff; padding: 14px 20px;">
          <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; color: #a7f3d0;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mb-0 fw-bold text-uppercase" style="font-size: 13.5px; letter-spacing: 0.04em; color: #ffffff;">
              SIAP LANJUT KE RAB
            </h3>
          </div>
          <button type="button" class="rounded-circle border-0 d-flex align-items-center justify-content-center text-white" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; width: 28px; height: 28px; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='rgba(0,0,0,0.15)'" onmouseout="this.style.background='transparent'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width: 16px; height: 16px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
          <!-- Success Banner Box -->
          <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px; display: flex; gap: 14px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" 
                 style="width: 32px; height: 32px; background-color: #d1fae5; color: #047857; font-size: 16px;">
              ✓
            </div>
            <div style="font-size: 12.5px; line-height: 1.5;">
              <p class="fw-bold mb-0.5" style="color: #064e3b; font-size: 12.5px;">
                Seluruh Pekerjaan Telah Terpetakan (<span id="proceedReadyItemCount">0</span> item)
              </p>
              <p class="mb-0" style="color: #065f46; font-size: 12px; margin-top: 2px;">
                Semua item pekerjaan telah berhasil dipetakan ke standar AHSP. Data siap disusun menjadi Rencana Anggaran Biaya (RAB).
              </p>
            </div>
          </div>

          <!-- Prompt Text -->
          <p class="text-center fw-semibold py-1 mb-0" style="font-size: 12.5px; color: #334155;">
            Lanjutkan ke halaman penyusunan Rencana Anggaran Biaya (RAB)?
          </p>

          <!-- Action Buttons -->
          <div class="pt-2 d-flex align-items-center justify-content-end gap-2.5" style="border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn btn-sm fw-semibold" data-bs-dismiss="modal" style="font-size: 12px; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; background-color: #ffffff; padding: 8px 16px; cursor: pointer;">
              Batal
            </button>
            <button type="button" class="btn btn-sm fw-bold text-white d-inline-flex align-items-center gap-1.5" onclick="confirmProceedToRab()" style="background-color: #00802b; border: none; border-radius: 8px; font-size: 12.5px; padding: 8px 20px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); cursor: pointer;" onmouseover="this.style.backgroundColor='#047857'" onmouseout="this.style.backgroundColor='#00802b'">
              <span>Lanjut ke RAB</span>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 14px; height: 14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Global context from backend
  const PROJECT_ID = "<?= esc($project['id']) ?>";
  const PROJECT_UUID = "<?= esc($project['uuid']) ?>";
  const PROJECT_TITLE = "<?= esc(addslashes($project['title'])) ?>";
  const PROJECT_CLIENT = "<?= esc(addslashes($project['client'] ?? 'Klien')) ?>";

  // State variables
  let currentMethod = 'file'; // 'file' | 'prompt'
  let selectedFile = null;
  let promptText = '';
  let isProcessing = false;
  let processInterval = null;
  let timerInterval = null;
  let triviaInterval = null;
  let currentSeconds = 0;
  let progressPercent = 0;

  const collapsedSections = {};
  let currentActiveItemId = null;

  // Preset Prompts
  const PRESET_PROMPTS = {
    'rumah-2-lantai': 'Pembangunan rumah tinggal minimalis modern 2 lantai ukuran 8x15 meter. Lantai 1: carport, teras, ruang tamu, ruang keluarga, 1 kamar tidur utama dengan kamar mandi dalam, dapur bersih, dan toilet tamu. Lantai 2: 2 kamar tidur anak, 1 kamar mandi luar, ruang keluarga, dan area jemur terbuka. Struktur beton bertulang K-250, dinding bata ringan plester aci finishing cat emulsi, lantai granit 60x60, atap rangka baja ringan genteng keramik, dan kusen aluminium.',
    'rumah-1-lantai': 'Pembangunan rumah tinggal sederhana 1 lantai tipe 45 ukuran kavling 6x12 meter. Terdiri dari teras depan, ruang tamu, ruang makan, 2 kamar tidur, 1 kamar mandi, dapur, dan halaman belakang. Pondasi batu kali lajur, struktur kolom praktis beton, dinding bata ringan diplester dan diaci, lantai keramik 40x40, atap rangka baja ringan penutup genteng metal berpasir, dan plafon gypsum board.',
    'ruko-2-lantai': 'Pembangunan ruko (rumah toko) 2 lantai ukuran 5x16 meter. Lantai 1 difungsikan untuk ruang usaha terbuka/plong, 1 kamar mandi, dan area tangga beton. Lantai 2 difungsikan untuk ruang kantor dengan 2 ruangan partisi gypsum, 1 kamar mandi, dan pantry. Menggunakan pondasi footplat beton bertulang, struktur beton bertulang, pintu depan folding gate besi, lantai homogenous tile 60x60, atap dak beton dan spandek.',
    'renovasi-dapur': 'Pekerjaan renovasi area dapur bersih dan 1 kamar mandi utama ukuran 3x5 meter. Meliputi pembongkaran keramik lama dan dinding partisi, pemasangan instalasi pipa air bersih dan air kotor baru, pemasangan meja dapur cor beton finishing granit, kitchen sink stainless steel, keramik dinding dapur subway tile 10x20, keramik lantai kamar mandi anti-slip 30x30, dinding kamar mandi full keramik sampai plafon, kloset duduk, shower set, dan plafon PVC anti air.'
  };

  const TRIVIA_LIST = [
    { title: "💡 Tips Kecepatan Konversi", text: "Untuk estimasi instan (< 30 detik), ekspor proyek Anda ke format IFC (.ifc) langsung dari software Revit / BIM Anda, lalu unggah file IFC tersebut di sini!" },
    { title: "🏗️ Mengapa Harus Data Asli?", text: "Sistem Estimasi menerapkan kebijakan '100% Real Data'. AI tidak mengarang volume secara sembarang, melainkan memetakan kuantitas nyata yang diekstrak secara matematis." },
    { title: "📊 Penomoran WBS & AHSP", text: "Setiap elemen pekerjaan yang dihasilkan AI dikelompokkan secara terstruktur berdasarkan standar Analisis Harga Satuan Pekerjaan (AHSP) Indonesia untuk memudahkan penyusunan RAB yang legal." },
    { title: "⚡ Standar Otomatisasi", text: "Sistem secara otomatis menghitung kebutuhan volume material turunannya (seperti bekisting & rebar) dan memetakan deskripsi RAB secara presisi." }
  ];

  // Modals instances
  let ahspModalInstance = null;
  let editVolumeModalInstance = null;
  let deleteModalInstance = null;
  let proceedModalInstance = null;

  document.addEventListener('DOMContentLoaded', function () {
    ahspModalInstance = new bootstrap.Modal(document.getElementById('ahspDetailModal'));
    editVolumeModalInstance = new bootstrap.Modal(document.getElementById('editVolumeModal'));
    deleteModalInstance = new bootstrap.Modal(document.getElementById('deleteItemModal'));
    proceedModalInstance = new bootstrap.Modal(document.getElementById('proceedRabModal'));
  });

  // ----------------------------------------------------------------
  // PILIH METODE DETEKSI LOGIC
  // ----------------------------------------------------------------
  function selectDetectMethod(method) {
    currentMethod = method;
    const optFile = document.getElementById('methodOptionFile');
    const optPrompt = document.getElementById('methodOptionPrompt');
    const subFile = document.getElementById('subformFile');
    const subPrompt = document.getElementById('subformPrompt');

    if (method === 'file') {
      optFile.classList.add('active');
      optPrompt.classList.remove('active');
      subFile.classList.remove('d-none');
      subPrompt.classList.add('d-none');
    } else {
      optFile.classList.remove('active');
      optPrompt.classList.add('active');
      subFile.classList.add('d-none');
      subPrompt.classList.remove('d-none');
    }
    updateMulaiButtonState();
  }

  function handleDedDragOver(e) {
    e.preventDefault();
    document.getElementById('dedDropzone').classList.add('dragover');
  }

  function handleDedDragLeave(e) {
    e.preventDefault();
    document.getElementById('dedDropzone').classList.remove('dragover');
  }

  function handleDedDrop(e) {
    e.preventDefault();
    document.getElementById('dedDropzone').classList.remove('dragover');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleDedFileSelected(e.dataTransfer.files);
    }
  }

  function handleDedFileSelected(files) {
    if (!files || files.length === 0) return;
    selectedFile = files[0];
    
    document.getElementById('dropzoneEmptyState').classList.add('d-none');
    const selState = document.getElementById('dropzoneSelectedState');
    selState.classList.remove('d-none');

    document.getElementById('selectedFileName').textContent = selectedFile.name;
    const sizeMb = (selectedFile.size / (1024 * 1024)).toFixed(2);
    document.getElementById('selectedFileSize').textContent = `${sizeMb} MB • Klik untuk ganti file`;

    showToast('File Terpilih', `Berkas "${selectedFile.name}" siap dianalisis.`, 'info');
    updateMulaiButtonState();
  }

  function handlePromptInput(text) {
    promptText = text;
    const charLen = text.trim().length;
    const counter = document.getElementById('promptCharCounter');
    counter.textContent = `${charLen} karakter (Min. 15)`;

    if (charLen >= 15) {
      counter.className = 'text-success small fw-semibold';
    } else {
      counter.className = 'text-muted small';
    }
    updateMulaiButtonState();
  }

  function applyPromptPreset(presetKey, btn) {
    const text = PRESET_PROMPTS[presetKey] || '';
    const textarea = document.getElementById('promptTextInput');
    textarea.value = text;
    handlePromptInput(text);

    // Highlight chip
    document.querySelectorAll('.prompt-chip').forEach(c => c.classList.remove('active'));
    if (btn) {
      btn.classList.add('active');
    }
  }

  function updateMulaiButtonState() {
    const btn = document.getElementById('btnMulaiDeteksi');
    if (!btn) return;

    if (currentMethod === 'file') {
      btn.disabled = !selectedFile;
    } else {
      btn.disabled = !promptText || promptText.trim().length < 15;
    }
  }

  function switchToDetectMode() {
    document.getElementById('wbsTableContainerSection').classList.add('d-none');
    document.getElementById('detectionSelectionCard').classList.remove('d-none');
    document.getElementById('detectionProcessingState').classList.add('d-none');
    document.getElementById('detectionFormSection').classList.remove('d-none');
    window.scrollTo({ top: 120, behavior: 'smooth' });
  }

  function cancelDetectMode() {
    document.getElementById('detectionSelectionCard').classList.add('d-none');
    document.getElementById('wbsTableContainerSection').classList.remove('d-none');
    window.scrollTo({ top: 120, behavior: 'smooth' });
  }

  // ----------------------------------------------------------------
  // START DETECTION PROCESS & STEPPER
  // ----------------------------------------------------------------
  async function startDetectionProcess() {
    isProcessing = true;
    document.getElementById('detectionFormSection').classList.add('d-none');
    document.getElementById('detectionProcessingState').classList.remove('d-none');

    const titleEl = document.getElementById('processTitle');
    const subTitleEl = document.getElementById('processDetailSubtitle');
    if (currentMethod === 'prompt') {
      titleEl.textContent = 'Memproses Deteksi Prompt AI...';
      subTitleEl.textContent = `Konsep: "${promptText.slice(0, 50)}..."`;
    } else {
      titleEl.textContent = 'Memproses Berkas Dokumen DED...';
      subTitleEl.textContent = `File: ${selectedFile.name}`;
    }

    // Timer
    currentSeconds = 0;
    const timerEl = document.getElementById('elapsedTimerText');
    timerInterval = setInterval(() => {
      currentSeconds++;
      const m = Math.floor(currentSeconds / 60).toString().padStart(2, '0');
      const s = (currentSeconds % 60).toString().padStart(2, '0');
      timerEl.textContent = `${m}:${s}`;
    }, 1000);

    // Trivia rotator
    let triviaIdx = 0;
    triviaInterval = setInterval(() => {
      triviaIdx = (triviaIdx + 1) % TRIVIA_LIST.length;
      document.getElementById('triviaTitle').textContent = TRIVIA_LIST[triviaIdx].title;
      document.getElementById('triviaText').textContent = TRIVIA_LIST[triviaIdx].text;
    }, 5000);

    // Stepper & Circle progress animation
    progressPercent = 5;
    updateProgressRing(progressPercent);

    processInterval = setInterval(() => {
      if (progressPercent < 90) {
        progressPercent += Math.floor(Math.random() * 6) + 3;
        if (progressPercent > 90) progressPercent = 90;
        updateProgressRing(progressPercent);
        updateStepperStep(progressPercent);
      }
    }, 450);

    // Call Backend API
    try {
      let response;
      if (currentMethod === 'prompt') {
        response = await fetch('<?= base_url('api/rab/analyze-prompt') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name: PROJECT_TITLE,
            client: PROJECT_CLIENT,
            prompt: promptText
          })
        });
      } else {
        const formData = new FormData();
        formData.append('name', PROJECT_TITLE);
        formData.append('client', PROJECT_CLIENT);
        formData.append('ded_file', selectedFile);
        formData.append('file', selectedFile);

        response = await fetch('<?= base_url('api/rab/analyze-image') ?>', {
          method: 'POST',
          body: formData
        });
      }

      let result;
      try {
        result = await response.json();
      } catch (parseErr) {
        throw new Error('Respons dari server tidak valid (bukan format JSON).');
      }

      if (!response.ok) {
        const errMsg = result?.message || result?.detail || result?.error || 'Gagal memproses estimasi AI.';
        throw new Error(errMsg);
      }

      // If backend analysis returned valid data, save estimation
      if (result) {
        try {
          const savePayload = result.data || result;
          const saveRes = await fetch(`<?= base_url('api/projects') ?>/${PROJECT_UUID}/save-estimation`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(savePayload)
          });
          const saveResult = await saveRes.json();
          console.log('Hasil penyimpanan estimasi:', saveResult);

          if (!saveRes.ok) {
            throw new Error(saveResult?.message || 'Gagal menyimpan hasil estimasi ke database.');
          }
        } catch (saveErr) {
          console.error('Gagal menyimpan estimasi ke database:', saveErr);
          throw saveErr;
        }
      }

      // Finish progress animation
      clearInterval(processInterval);
      clearInterval(timerInterval);
      clearInterval(triviaInterval);

      updateProgressRing(100);
      updateStepperStep(100);

      setTimeout(() => {
        showToast('Estimasi Berhasil!', 'Rincian estimasi anggaran proyek telah berhasil dideteksi dan disimpan.', 'success');
        // Reload page to reflect actual database runs & sections
        window.location.href = `<?= base_url('anggaran') ?>?id=${encodeURIComponent(PROJECT_UUID)}`;
      }, 750);

    } catch (err) {
      clearInterval(processInterval);
      clearInterval(timerInterval);
      clearInterval(triviaInterval);
      
      console.error('Detection error:', err);
      showToast('Deteksi Gagal', err.message || 'Terjadi kesalahan saat memproses estimasi.', 'error');

      // Kembalikan tampilan ke form awal agar user bisa memperbaiki prompt/file dan mencoba lagi
      document.getElementById('detectionProcessingState').classList.add('d-none');
      document.getElementById('detectionFormSection').classList.remove('d-none');
      isProcessing = false;
    }
  }

  function updateProgressRing(percent) {
    const circle = document.getElementById('progressCircleBar');
    const text = document.getElementById('progressTextPercent');
    const totalLength = 264; // 2 * PI * 42
    const offset = totalLength - (percent / 100 * totalLength);
    if (circle) circle.style.strokeDashoffset = offset;
    if (text) text.textContent = `${Math.round(percent)}%`;
  }

  function updateStepperStep(percent) {
    const steps = [
      document.getElementById('stepItem0'),
      document.getElementById('stepItem1'),
      document.getElementById('stepItem2'),
      document.getElementById('stepItem3')
    ];

    let currentStep = 0;
    if (percent >= 25 && percent < 50) currentStep = 1;
    else if (percent >= 50 && percent < 75) currentStep = 2;
    else if (percent >= 75) currentStep = 3;

    steps.forEach((stepEl, idx) => {
      if (!stepEl) return;
      const indicator = stepEl.querySelector('.step-indicator');
      if (idx < currentStep) {
        stepEl.className = 'processing-step-item completed';
        if (indicator) indicator.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
      } else if (idx === currentStep) {
        stepEl.className = 'processing-step-item active';
        if (indicator) indicator.innerHTML = '<div class="spinner-border spinner-border-sm text-success" role="status"></div>';
      } else {
        stepEl.className = 'processing-step-item text-muted opacity-50';
        if (indicator) indicator.innerHTML = '<i class="bi bi-circle"></i>';
      }
    });
  }

  // ----------------------------------------------------------------
  // WBS TABLE LOGIC
  // ----------------------------------------------------------------
  function toggleSection(sectionCode) {
    const isCurrentlyCollapsed = !!collapsedSections[sectionCode];
    collapsedSections[sectionCode] = !isCurrentlyCollapsed;

    const btn = document.getElementById(`btn-sec-${sectionCode}`);
    const rows = document.querySelectorAll(`.wbs-item-row[data-section-code="${sectionCode}"]`);

    if (collapsedSections[sectionCode]) {
      rows.forEach(r => r.classList.add('d-none'));
      if (btn) btn.innerHTML = '<i class="bi bi-plus"></i>';
    } else {
      rows.forEach(r => r.classList.remove('d-none'));
      if (btn) btn.innerHTML = '<i class="bi bi-dash"></i>';
    }
  }

  function handleSearch(query) {
    const term = query.trim().toLowerCase();
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) {
      clearBtn.classList.toggle('d-none', !term);
    }

    const sectionRows = document.querySelectorAll('.wbs-section-row');
    const emptyRow = document.getElementById('emptySearchRow');
    let totalVisibleItems = 0;

    sectionRows.forEach(secRow => {
      const code = secRow.dataset.sectionCode;
      const secTitle = secRow.querySelector('.wbs-section-title')?.textContent.toLowerCase() || '';
      const itemRows = document.querySelectorAll(`.wbs-item-row[data-section-code="${code}"]`);

      let matchInSection = 0;
      itemRows.forEach(itemRow => {
        const name = (itemRow.dataset.itemName || '').toLowerCase();
        const ahspCode = (itemRow.dataset.ahspCode || '').toLowerCase();
        const ahspName = (itemRow.dataset.ahspName || '').toLowerCase();

        const matches = !term || name.includes(term) || ahspCode.includes(term) || ahspName.includes(term) || secTitle.includes(term);
        if (matches) {
          itemRow.classList.remove('d-none');
          matchInSection++;
          totalVisibleItems++;
        } else {
          itemRow.classList.add('d-none');
        }
      });

      if (!term || matchInSection > 0 || secTitle.includes(term)) {
        secRow.classList.remove('d-none');
      } else {
        secRow.classList.add('d-none');
      }
    });

    if (emptyRow) {
      emptyRow.classList.toggle('d-none', totalVisibleItems > 0 || !term);
    }
  }

  function clearSearch() {
    const input = document.getElementById('wbsSearchInput');
    if (input) {
      input.value = '';
      handleSearch('');
      input.focus();
    }
  }

  function openAhspModal(itemId) {
    window.location.href = `<?= base_url('pemetaan-ahsp') ?>?id=${encodeURIComponent(PROJECT_UUID)}&item=${encodeURIComponent(itemId)}`;
  }

  function openEditVolumeModal(itemId) {
    currentActiveItemId = itemId;
    const row = document.querySelector(`.wbs-item-row[data-item-id="${itemId}"]`);
    if (!row) return;

    const name = row.dataset.itemName || '';
    const volume = parseFloat(row.dataset.itemVolume) || 0;
    const unit = row.dataset.itemUnit || 'm2';

    document.getElementById('editVolumeItemId').value = itemId;
    document.getElementById('editVolumeItemTitle').textContent = name;
    document.getElementById('editVolumeInput').value = volume;
    document.getElementById('editVolumeUnitSpan').textContent = unit;

    editVolumeModalInstance.show();
  }

  function saveVolumeEdit() {
    const itemId = document.getElementById('editVolumeItemId').value;
    const newVol = parseFloat(document.getElementById('editVolumeInput').value) || 0;

    const row = document.querySelector(`.wbs-item-row[data-item-id="${itemId}"]`);
    if (row) {
      row.dataset.itemVolume = newVol;
      const volSpan = row.querySelector('.volume-text');
      if (volSpan) {
        volSpan.textContent = newVol.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    }

    if (editVolumeModalInstance) editVolumeModalInstance.hide();
    showToast('Volume Diperbarui', `Volume pekerjaan berhasil diubah menjadi ${newVol.toLocaleString('id-ID', { minimumFractionDigits: 2 })}.`, 'success');
  }

  function confirmDeleteItem(itemId, itemName) {
    document.getElementById('deleteTargetItemId').value = itemId;
    document.getElementById('deleteItemModalName').textContent = `Apakah Anda yakin ingin menghapus "${itemName}" dari daftar estimasi?`;
    deleteModalInstance.show();
  }

  function executeDeleteItem() {
    const itemId = document.getElementById('deleteTargetItemId').value;
    const row = document.querySelector(`.wbs-item-row[data-item-id="${itemId}"]`);
    const itemName = row ? (row.dataset.itemName || 'Pekerjaan') : 'Pekerjaan';

    if (row) {
      row.remove();
      updateTotalItemCounter();
    }

    deleteModalInstance.hide();
    showToast('Pekerjaan Dihapus', `"${itemName}" berhasil dihapus dari estimasi proyek.`, 'warning');
  }

  function updateTotalItemCounter() {
    const count = document.querySelectorAll('.wbs-item-row').length;
    const totalEl = document.getElementById('totalItemCount');
    if (totalEl) totalEl.textContent = count;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function handleLanjutKeRab() {
    const allRows = Array.from(document.querySelectorAll('.wbs-item-row'));
    const unmappedRows = allRows.filter(r => {
      const status = (r.dataset.ahspStatus || '').trim().toLowerCase();
      const hasWarning = r.dataset.hasWarning === '1';
      return status === 'unmapped' || hasWarning;
    });

    const unmappedSection = document.getElementById('proceedUnmappedContent');
    const readySection = document.getElementById('proceedReadyContent');

    if (unmappedRows.length > 0) {
      if (unmappedSection) unmappedSection.classList.remove('d-none');
      if (readySection) readySection.classList.add('d-none');

      const countEl = document.getElementById('proceedWarningCount');
      const listCountEl = document.getElementById('proceedListCount');
      if (countEl) countEl.textContent = unmappedRows.length;
      if (listCountEl) listCountEl.textContent = unmappedRows.length;

      const listContainer = document.getElementById('unmappedItemsList');
      listContainer.innerHTML = '';

      unmappedRows.forEach(r => {
        const itemId = r.dataset.itemId || '';
        const name = r.dataset.itemName || '';
        const secCode = r.dataset.sectionCode || '-';
        const unit = r.dataset.itemUnit || '';
        const vol = parseFloat(r.dataset.itemVolume) || 0;

        const rowDiv = document.createElement('div');
        rowDiv.className = 'd-flex align-items-center justify-content-between gap-3 border-bottom';
        rowDiv.style.padding = '10px 14px';
        rowDiv.style.borderColor = '#f1f5f9';
        rowDiv.style.transition = 'background-color 0.15s ease';
        rowDiv.onmouseover = function() { this.style.backgroundColor = '#ffffff'; };
        rowDiv.onmouseout = function() { this.style.backgroundColor = 'transparent'; };

        rowDiv.innerHTML = `
          <div class="d-flex align-items-start gap-2.5 flex-grow-1" style="min-width: 0;">
            <span class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                  style="width: 20px; height: 20px; background-color: #fee2e2; color: #b91c1c; font-size: 10.5px; margin-top: 1px;">
              !
            </span>
            <div class="flex-grow-1" style="min-width: 0;">
              <p class="mb-0 fw-semibold text-truncate" style="font-size: 12px; color: #1e293b;" title="${escapeHtml(name)}">
                ${escapeHtml(name)}
              </p>
              <div class="d-flex align-items-center gap-2 mt-0.5" style="font-size: 11px; color: #64748b;">
                <span style="background-color: rgba(226, 232, 240, 0.8); padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 500; color: #334155;">
                  Kategori ${escapeHtml(secCode)}
                </span>
                <span>•</span>
                <span>Vol: ${vol.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(unit)}</span>
              </div>
            </div>
          </div>

          <a href="<?= base_url('pemetaan-ahsp') ?>?id=${encodeURIComponent(PROJECT_UUID)}&item=${encodeURIComponent(itemId)}"
             class="flex-shrink-0 d-inline-flex align-items-center gap-1 text-decoration-none"
             style="font-size: 11px; font-weight: 700; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 4px 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); transition: background-color 0.15s ease;"
             onmouseover="this.style.backgroundColor='#d1fae5'"
             onmouseout="this.style.backgroundColor='#ecfdf5'"
             title="Petakan pekerjaan ini sekarang">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 12px; height: 12px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
            <span>Petakan</span>
          </a>
        `;
        listContainer.appendChild(rowDiv);
      });

      proceedModalInstance.show();
    } else {
      if (unmappedSection) unmappedSection.classList.add('d-none');
      if (readySection) readySection.classList.remove('d-none');
      const readyItemCountEl = document.getElementById('proceedReadyItemCount');
      if (readyItemCountEl) readyItemCountEl.textContent = allRows.length;
      proceedModalInstance.show();
    }
  }

  function confirmProceedToRab() {
    if (proceedModalInstance) proceedModalInstance.hide();
    showToast('Lanjut ke RAB', 'Menyimpan struktur estimasi dan beralih ke tahap penyusunan Rencana Anggaran Biaya (RAB)...', 'success');
    setTimeout(() => {
      window.location.href = `<?= base_url('rab') ?>?id=${encodeURIComponent(PROJECT_UUID)}`;
    }, 700);
  }
</script>
<?= $this->endSection() ?>
