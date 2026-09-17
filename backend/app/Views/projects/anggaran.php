<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Hasil Deteksi - <?= esc($project['title']) ?> | Estimator.id
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* ------------------------------------------------------------- */
  /* HERO BANNER (MATCHING FRONTEND & RAB DESIGN)                  */
  /* ------------------------------------------------------------- */
  .anggaran-banner {
    background-color: var(--brand-banner-bg);
    position: relative;
    height: 112px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: inset 0 -2px 6px rgba(0, 0, 0, 0.05);
  }
  .anggaran-banner-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-position: center right;
    pointer-events: none;
    z-index: 1;
  }
  .anggaran-banner-title {
    position: relative;
    z-index: 2;
    color: #ffffff;
    font-size: 24px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin: 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }
  @media (min-width: 768px) {
    .anggaran-banner-title {
      font-size: 28px;
    }
  }

  /* ------------------------------------------------------------- */
  /* MAIN CARD WORKSPACE                                           */
  /* ------------------------------------------------------------- */
  .anggaran-workspace-card {
    max-width: 1360px;
    margin: 24px auto 50px auto;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    padding: 24px;
  }

  /* ------------------------------------------------------------- */
  /* PILIH METODE DETEKSI STYLES                                  */
  /* ------------------------------------------------------------- */
  .detect-selection-card {
    max-width: 840px;
    margin: 10px auto 30px auto;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 32px 36px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  }
  .detect-method-option {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 16px;
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

  /* Dropzone */
  .ded-dropzone {
    border: 2px dashed #0fa83c;
    background-color: #f7fdf9;
    border-radius: 12px;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .ded-dropzone:hover, .ded-dropzone.dragover {
    background-color: #ecfdf5;
    border-color: #059669;
    box-shadow: 0 0 0 4px rgba(15, 168, 60, 0.1);
  }
  .upload-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: #e6f7ec;
    color: #089613;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    border: 1px solid #bbf7d0;
  }

  /* Prompt Input & Chips */
  .prompt-textarea {
    width: 100%;
    background-color: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
    line-height: 1.5;
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
    font-size: 12px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
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
    font-size: 14px;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    padding: 10px 28px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 3px 10px rgba(8, 150, 19, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-mulai-deteksi:hover:not(:disabled) {
    background-color: #06730e;
    box-shadow: 0 5px 14px rgba(8, 150, 19, 0.35);
    color: #ffffff;
  }
  .btn-mulai-deteksi:disabled {
    background-color: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
  }

  /* Stepper */
  .processing-step-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
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
  /* SEARCH TOOLBAR & WBS TABLE VIEW (MATCHING FRONTEND & RAB)     */
  /* ------------------------------------------------------------- */
  .anggaran-search-box {
    position: relative;
    max-width: 420px;
    width: 100%;
  }
  .anggaran-search-input {
    width: 100%;
    height: 38px;
    padding: 6px 32px 6px 34px;
    font-size: 12.5px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
    color: #334155;
    transition: all 0.15s ease;
  }
  .anggaran-search-input:focus {
    outline: none;
    border-color: #0fa83c;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(15, 168, 60, 0.12);
  }
  .anggaran-search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 14px;
  }
  .anggaran-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
  }
  .anggaran-search-clear:hover {
    color: #475569;
  }

  /* WBS Table Container */
  .wbs-table-container {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background-color: #ffffff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    overflow: hidden;
  }
  .wbs-table-scroll-area {
    max-height: 65vh;
    overflow-x: auto;
    overflow-y: auto;
    position: relative;
  }
  .wbs-table-scroll-area::-webkit-scrollbar {
    width: 7px;
    height: 7px;
  }
  .wbs-table-scroll-area::-webkit-scrollbar-track {
    background: #f8fafc;
  }
  .wbs-table-scroll-area::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }

  .wbs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    margin-bottom: 0;
  }
  .wbs-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #089613;
  }
  .wbs-table th {
    background-color: #089613 !important;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 14px;
    letter-spacing: 0.02em;
    border: none;
    white-space: nowrap;
    user-select: none;
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
    padding: 10px 14px !important;
  }
  .btn-toggle-section {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background-color: #d32f2f;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    margin: 0 auto;
    transition: transform 0.1s ease, background-color 0.15s ease;
    box-shadow: 0 1px 2px rgba(211, 47, 47, 0.25);
  }
  .btn-toggle-section:hover {
    background-color: #b71c1c;
  }
  .minus-line {
    width: 8px;
    height: 2px;
    background-color: #ffffff;
    border-radius: 9999px;
    display: block;
  }
  .plus-icon {
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
    display: block;
  }
  .wbs-section-title {
    font-size: 12.5px;
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
    padding: 10px 14px !important;
    vertical-align: middle;
  }
  .wbs-item-no {
    color: #64748b;
    font-size: 12px;
    text-align: center;
    font-weight: 500;
  }
  .wbs-item-name {
    font-size: 12.5px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
  }
  .badge-unmapped {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 1px 6px;
    background-color: #fee2e2;
    border: 1px solid #f87171;
    color: #991b1b;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 6px;
    vertical-align: middle;
  }
  .wbs-item-volume {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    text-align: center;
    font-variant-numeric: tabular-nums;
  }
  .wbs-item-unit {
    font-size: 12px;
    color: #475569;
    text-align: center;
  }

  /* Action Buttons */
  .action-icon-btn {
    background: none;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    padding: 0;
  }
  .action-icon-book {
    color: #047857;
  }
  .action-icon-book:hover {
    color: #065f46;
    background-color: #ecfdf5;
  }
  .action-icon-trash {
    color: #ef4444;
  }
  .action-icon-trash:hover {
    color: #dc2626;
    background-color: #fee2e2;
  }

  /* Total Items Bar */
  .wbs-total-bar {
    background-color: #00802b;
    color: #ffffff;
    font-weight: 700;
    font-size: 13.5px;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    letter-spacing: 0.02em;
    user-select: none;
  }

  /* Bottom Actions */
  .btn-lanjut-rab {
    background-color: #00802b;
    color: #ffffff;
    font-size: 13.5px;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    padding: 10px 28px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 3px 10px rgba(0, 128, 43, 0.25);
  }
  .btn-lanjut-rab:hover {
    background-color: #006e24;
    box-shadow: 0 5px 14px rgba(0, 128, 43, 0.35);
    color: #ffffff;
  }

  /* Modal Backdrop Blur */
  .modal-backdrop.show {
    background-color: rgba(15, 23, 42, 0.6) !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
    opacity: 1 !important;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Banner with Green Wavy Pattern & Project Title -->
<div class="anggaran-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Estimator" class="anggaran-banner-bg" onerror="this.style.display='none'">
  <h1 class="anggaran-banner-title"><?= esc($project['title']) ?></h1>
</div>

<!-- Main Workspace Container -->
<main class="container-fluid px-3 px-md-4">
  <div class="anggaran-workspace-card">

    <!-- ============================================================== -->
    <!-- 1. PILIH METODE DETEKSI CARD                                   -->
    <!-- ============================================================== -->
    <div id="detectionSelectionCard" class="detect-selection-card <?= $showDetect ? '' : 'd-none' ?>">

      <!-- Processing State (Initially Hidden) -->
      <div id="detectionProcessingState" class="d-none">
        <div class="d-flex align-items-center gap-4 pb-4 border-bottom border-light-subtle mb-4">
          <!-- Circular Progress Ring -->
          <div class="position-relative d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; flex-shrink: 0;">
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
            <span id="progressTextPercent" class="position-absolute fw-bolder text-dark" style="font-size: 16px;">
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
        <div class="text-center mb-4">
          <h2 class="fw-bold text-dark fs-5 mb-1">Pilih Metode Deteksi</h2>
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
              <div style="height: 65px; width: 100%; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 120 80" style="width: 100%; height: 65px;" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="12" y="10" width="96" height="60" rx="6" fill="#f0faf1" stroke="#089613" stroke-width="1.8" />
                  <line x1="12" y1="30" x2="108" y2="30" stroke="#bbf7d0" stroke-width="1" stroke-dasharray="2 2" />
                  <line x1="12" y1="50" x2="108" y2="50" stroke="#bbf7d0" stroke-width="1" stroke-dasharray="2 2" />
                  <rect x="24" y="20" width="38" height="40" rx="2" fill="white" stroke="#089613" stroke-width="1.8" />
                  <path d="M78 26L94 18L102 23L86 31Z" fill="#dcfce7" stroke="#089613" stroke-width="1.5" />
                  <path d="M78 26V46L86 51V31Z" fill="#bbf7d0" stroke="#089613" stroke-width="1.5" />
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
              <div style="height: 65px; width: 100%; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 120 80" style="width: 100%; height: 65px;" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="16" y="10" width="88" height="60" rx="6" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.8" />
                  <rect x="28" y="22" width="34" height="4" rx="2" fill="#64748b" />
                  <rect x="28" y="32" width="54" height="3" rx="1.5" fill="#cbd5e1" />
                  <rect x="28" y="40" width="46" height="3" rx="1.5" fill="#cbd5e1" />
                  <path d="M86 20L87.5 14L93.5 12.5L87.5 11L86 5L84.5 11L78.5 12.5L84.5 14Z" fill="#089613" />
                </svg>
              </div>
              <div class="detect-method-title">Prompt AI</div>
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
                <i class="bi bi-cloud-arrow-up fs-4"></i>
              </div>
              <div class="fw-bold text-dark mb-1" style="font-size: 14.5px;">
                Pilih file dokumen DED atau seret kemari
              </div>
              <div class="text-muted small">
                Mendukung format: PDF, DWG, DXF, IFC, RVT, SKP, JPEG, PNG (Maks. 500MB)
              </div>
            </div>

            <div id="dropzoneSelectedState" class="d-none">
              <div class="upload-icon-circle" style="background-color: #089613; color: #ffffff; border-color: #089613;">
                <i class="bi bi-check-lg fs-4"></i>
              </div>
              <div class="fw-bold text-dark mb-1" id="selectedFileName" style="font-size: 14.5px;">
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
            rows="4" 
            class="prompt-textarea" 
            placeholder="Tuliskan spesifikasi teknis atau gambaran bangunan...&#10;Contoh: Pembangunan rumah tinggal minimalis 2 lantai ukuran 8x15 meter. Lantai 1 terdapat carport, ruang tamu, ruang keluarga, 1 kamar tidur, dapur, dan kamar mandi..."
            oninput="handlePromptInput(this.value)"
          ></textarea>

          <!-- Quick Examples (Chips) -->
          <div class="mt-3">
            <div class="small fw-bold text-secondary mb-2" style="font-size: 12px;">
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
    <!-- 2. WBS TABLE VIEW WORKSPACE                                    -->
    <!-- ============================================================== -->
    <div id="wbsTableContainerSection" class="<?= $showDetect ? 'd-none' : '' ?>">

      <!-- Action Toolbar: Search Control -->
      <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3 pb-2">
        <div class="anggaran-search-box">
          <i class="bi bi-search anggaran-search-icon"></i>
          <input 
            type="text" 
            id="wbsSearchInput" 
            class="anggaran-search-input" 
            placeholder="Cari pekerjaan, seksi, atau kode AHSP..." 
            oninput="handleSearch(this.value)"
          >
          <button 
            type="button" 
            id="clearSearchBtn" 
            class="anggaran-search-clear d-none" 
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
                <th style="width: 50px;" class="text-center">No.</th>
                <th>Uraian Pekerjaan</th>
                <th style="width: 120px;" class="text-center">Volume</th>
                <th style="width: 90px;" class="text-center">Satuan</th>
                <th style="width: 90px;" class="text-center">Aksi</th>
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
                        <span class="minus-line" id="icon-sec-<?= esc($sec['code']) ?>"></span>
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
                            <span class="badge-unmapped" title="Pekerjaan belum dipetakan ke standar AHSP">!</span>
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
      <div class="d-flex justify-content-end mt-4">
        <button type="button" class="btn-lanjut-rab" onclick="handleLanjutKeRab()">
          Lanjut ke RAB
        </button>
      </div>

    </div>

  </div>
</main>

<!-- Modal: Edit Volume Pekerjaan -->
<div class="modal fade" id="editVolumeModal" tabindex="-1" aria-labelledby="editVolumeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-dark text-white py-2.5 px-3">
        <h6 class="modal-title fs-6 fw-bold mb-0" id="editVolumeModalLabel">
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
        <div class="w-12 h-12 rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
          <i class="bi bi-trash fs-4"></i>
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

<!-- Modal: Konfirmasi Lanjut ke RAB (Matching Frontend Warning/Success Dialogs) -->
<div class="modal fade" id="proceedRabModal" tabindex="-1" aria-labelledby="proceedRabModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 512px;">
    <div class="modal-content border-0 shadow-2xl" style="border-radius: 16px; overflow: hidden;">
      
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
          <button type="button" class="rounded-circle border-0 d-flex align-items-center justify-content-center text-white" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; width: 28px; height: 28px; cursor: pointer;" onmouseover="this.style.background='rgba(0,0,0,0.15)'" onmouseout="this.style.background='transparent'">
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
          <button type="button" class="rounded-circle border-0 d-flex align-items-center justify-content-center text-white" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; width: 28px; height: 28px; cursor: pointer;" onmouseover="this.style.background='rgba(0,0,0,0.15)'" onmouseout="this.style.background='transparent'">
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

  // State variables
  let currentMethod = 'file'; // 'file' | 'prompt'
  let selectedFile = null;
  let promptText = '';
  let isProcessing = false;
  let timerInterval = null;
  let currentSeconds = 0;
  let progressPercent = 0;

  const collapsedSections = {};

  // Preset Prompts
  const PRESET_PROMPTS = {
    'rumah-2-lantai': 'Pembangunan rumah tinggal minimalis modern 2 lantai ukuran 8x15 meter. Lantai 1: carport, teras, ruang tamu, ruang keluarga, 1 kamar tidur utama dengan kamar mandi dalam, dapur bersih, dan toilet tamu. Lantai 2: 2 kamar tidur anak, 1 kamar mandi luar, ruang keluarga, dan area jemur terbuka. Struktur beton bertulang K-250, dinding bata ringan plester aci finishing cat emulsi, lantai granit 60x60, atap rangka baja ringan genteng keramik, dan kusen aluminium.',
    'rumah-1-lantai': 'Pembangunan rumah tinggal sederhana 1 lantai tipe 45 ukuran kavling 6x12 meter. Terdiri dari teras depan, ruang tamu, ruang makan, 2 kamar tidur, 1 kamar mandi, dapur, dan halaman belakang. Pondasi batu kali lajur, struktur kolom praktis beton, dinding bata ringan diplester dan diaci, lantai keramik 40x40, atap rangka baja ringan penutup genteng metal berpasir, dan plafon gypsum board.',
    'ruko-2-lantai': 'Pembangunan ruko (rumah toko) 2 lantai ukuran 5x16 meter. Lantai 1 difungsikan untuk ruang usaha terbuka/plong, 1 kamar mandi, dan area tangga beton. Lantai 2 difungsikan untuk ruang kantor dengan 2 ruangan partisi gypsum, 1 kamar mandi, dan pantry. Menggunakan pondasi footplat beton bertulang, struktur beton bertulang, pintu depan folding gate besi, lantai homogenous tile 60x60, atap dak beton dan spandek.',
    'renovasi-dapur': 'Pekerjaan renovasi area dapur bersih dan 1 kamar mandi utama ukuran 3x5 meter. Meliputi pembongkaran keramik lama dan dinding partisi, pemasangan instalasi pipa air bersih dan air kotor baru, pemasangan meja dapur cor beton finishing granit, kitchen sink stainless steel, keramik dinding dapur subway tile 10x20, keramik lantai kamar mandi anti-slip 30x30, dinding kamar mandi full keramik sampai plafon, kloset duduk, shower set, dan plafon PVC anti air.'
  };

  // Modals instances
  let editVolumeModalInstance = null;
  let deleteModalInstance = null;
  let proceedModalInstance = null;

  document.addEventListener('DOMContentLoaded', function () {
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

    if (typeof showToast === 'function') {
      showToast('File Terpilih', `Berkas "${selectedFile.name}" siap dianalisis.`, 'info');
    }
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

  function setStepperStep(activeStepIndex) {
    for (let i = 0; i < 4; i++) {
      const el = document.getElementById(`stepItem${i}`);
      if (!el) continue;
      const indicator = el.querySelector('.step-indicator');
      const label = el.querySelector('.step-label');

      if (i < activeStepIndex) {
        el.className = 'processing-step-item';
        if (indicator) indicator.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-6"></i>';
        if (label) label.className = 'small fw-semibold text-dark step-label';
      } else if (i === activeStepIndex) {
        el.className = 'processing-step-item';
        if (indicator) indicator.innerHTML = '<div class="spinner-border spinner-border-sm text-success" role="status"></div>';
        if (label) label.className = 'small fw-semibold text-dark step-label';
      } else {
        el.className = 'processing-step-item text-muted opacity-50';
        if (indicator) indicator.innerHTML = '<i class="bi bi-circle"></i>';
        if (label) label.className = 'small fw-semibold step-label';
      }
    }
  }

  function updateProgressCircle(percent) {
    const circleBar = document.getElementById('progressCircleBar');
    const percentText = document.getElementById('progressTextPercent');
    const clamped = Math.min(100, Math.max(0, percent));
    if (circleBar) {
      const offset = 264 - (264 * clamped / 100);
      circleBar.style.strokeDashoffset = offset;
    }
    if (percentText) {
      percentText.textContent = `${Math.round(clamped)}%`;
    }
  }

  async function startDetectionProcess() {
    isProcessing = true;
    document.getElementById('detectionFormSection').classList.add('d-none');
    document.getElementById('detectionProcessingState').classList.remove('d-none');

    const titleEl = document.getElementById('processTitle');
    const subTitleEl = document.getElementById('processDetailSubtitle');
    if (currentMethod === 'prompt') {
      titleEl.textContent = 'Memproses Deteksi Prompt AI...';
      subTitleEl.textContent = `Konsep: "${promptText.slice(0, 45)}..."`;
    } else {
      titleEl.textContent = 'Memproses Berkas Dokumen DED...';
      subTitleEl.textContent = `File: ${selectedFile.name}`;
    }

    currentSeconds = 0;
    const timerEl = document.getElementById('elapsedTimerText');
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      currentSeconds++;
      const m = Math.floor(currentSeconds / 60).toString().padStart(2, '0');
      const s = (currentSeconds % 60).toString().padStart(2, '0');
      if (timerEl) timerEl.textContent = `${m}:${s}`;
    }, 1000);

    // Animasi progress bar dinamis saat menunggu respon AI
    let currentProgress = 5;
    updateProgressCircle(currentProgress);
    setStepperStep(0);

    const progressInterval = setInterval(() => {
      if (currentProgress < 25) {
        currentProgress += 3;
        setStepperStep(0);
      } else if (currentProgress < 55) {
        currentProgress += 1.5;
        setStepperStep(1);
      } else if (currentProgress < 80) {
        currentProgress += 1;
        setStepperStep(2);
      } else if (currentProgress < 90) {
        currentProgress += 0.5;
        setStepperStep(3);
      }
      updateProgressCircle(currentProgress);
    }, 400);

    try {
      let aiResult = null;

      if (currentMethod === 'prompt') {
        const response = await fetch('/api/rab/analyze-prompt', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            name: "<?= esc($project['title']) ?>",
            client: "<?= esc($project['client'] ?? 'Client') ?>",
            prompt: promptText
          })
        });

        const resJson = await response.json().catch(() => ({}));
        if (!response.ok || (resJson && resJson.success === false)) {
          throw new Error(resJson.message || resJson.detail || resJson.error || `Gagal menganalisis prompt (${response.status})`);
        }
        aiResult = resJson;
      } else {
        const formData = new FormData();
        formData.append('ded_file', selectedFile);
        formData.append('name', "<?= esc($project['title']) ?>");
        formData.append('client', "<?= esc($project['client'] ?? 'Client') ?>");

        const response = await fetch('/api/rab/analyze-image', {
          method: 'POST',
          body: formData
        });

        const resJson = await response.json().catch(() => ({}));
        if (!response.ok || (resJson && resJson.success === false)) {
          throw new Error(resJson.message || resJson.detail || resJson.error || `Gagal menganalisis berkas (${response.status})`);
        }
        aiResult = resJson;
      }

      // Step 4: Simpan hasil AI ke database proyek
      clearInterval(progressInterval);
      updateProgressCircle(95);
      setStepperStep(3);
      if (subTitleEl) subTitleEl.textContent = 'Menyimpan rincian estimasi WBS ke basis data...';

      const activeProjectIdentifier = PROJECT_UUID || PROJECT_ID;
      const saveResponse = await fetch('/api/projects/' + encodeURIComponent(activeProjectIdentifier) + '/save-estimation', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(aiResult)
      });

      const saveJson = await saveResponse.json().catch(() => ({}));
      if (!saveResponse.ok || (saveJson && saveJson.status >= 400)) {
        throw new Error(saveJson.messages?.error || saveJson.message || 'Gagal menyimpan hasil estimasi ke database.');
      }

      // 100% selesai
      updateProgressCircle(100);
      setStepperStep(4);
      clearInterval(timerInterval);

      if (typeof showToast === 'function') {
        showToast('Deteksi Berhasil!', 'Data WBS & Pekerjaan telah berhasil dianalisis dan disimpan.', 'success');
      }

      setTimeout(() => {
        window.location.href = '/anggaran?id=' + encodeURIComponent(activeProjectIdentifier);
      }, 700);

    } catch (err) {
      clearInterval(progressInterval);
      clearInterval(timerInterval);
      isProcessing = false;

      console.error('Error saat deteksi:', err);
      if (typeof showToast === 'function') {
        showToast('Gagal Deteksi', err.message || 'Terjadi kesalahan saat memproses estimasi.', 'danger');
      } else {
        alert('Gagal Deteksi: ' + (err.message || 'Terjadi kesalahan.'));
      }

      // Kembalikan form deteksi agar user bisa mencoba kembali
      document.getElementById('detectionProcessingState').classList.add('d-none');
      document.getElementById('detectionFormSection').classList.remove('d-none');
      updateProgressCircle(0);
      setStepperStep(0);
    }
  }

  // ----------------------------------------------------------------
  // WBS TABLE INTERACTION (TOGGLE, SEARCH, EDIT, DELETE)
  // ----------------------------------------------------------------
  function toggleSection(secCode) {
    const rows = document.querySelectorAll(`.wbs-item-row[data-section-code="${secCode}"]`);
    const btn = document.getElementById(`btn-sec-${secCode}`);
    if (!btn) return;

    const isCollapsed = collapsedSections[secCode] || false;
    const nextState = !isCollapsed;
    collapsedSections[secCode] = nextState;

    rows.forEach(r => {
      if (nextState) {
        r.classList.add('d-none');
      } else {
        r.classList.remove('d-none');
      }
    });

    if (nextState) {
      btn.innerHTML = '<span class="plus-icon">+</span>';
    } else {
      btn.innerHTML = '<span class="minus-line"></span>';
    }
  }

  function handleSearch(query) {
    const q = query.trim().toLowerCase();
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) {
      if (q.length > 0) {
        clearBtn.classList.remove('d-none');
      } else {
        clearBtn.classList.add('d-none');
      }
    }

    const secRows = document.querySelectorAll('.wbs-section-row');
    const itemRows = document.querySelectorAll('.wbs-item-row');
    let visibleCount = 0;

    if (!q) {
      secRows.forEach(sr => sr.classList.remove('d-none'));
      itemRows.forEach(ir => {
        const secCode = ir.getAttribute('data-section-code');
        if (!collapsedSections[secCode]) {
          ir.classList.remove('d-none');
        }
      });
      document.getElementById('emptySearchRow').classList.add('d-none');
      return;
    }

    const visibleSections = new Set();
    itemRows.forEach(ir => {
      const name = (ir.getAttribute('data-item-name') || '').toLowerCase();
      const code = (ir.getAttribute('data-ahsp-code') || '').toLowerCase();
      const ahspName = (ir.getAttribute('data-ahsp-name') || '').toLowerCase();
      const secCode = ir.getAttribute('data-section-code');

      if (name.includes(q) || code.includes(q) || ahspName.includes(q)) {
        ir.classList.remove('d-none');
        visibleSections.add(secCode);
        visibleCount++;
      } else {
        ir.classList.add('d-none');
      }
    });

    secRows.forEach(sr => {
      const secCode = sr.getAttribute('data-section-code');
      const secTitle = sr.querySelector('.wbs-section-title').textContent.toLowerCase();
      if (visibleSections.has(secCode) || secTitle.includes(q)) {
        sr.classList.remove('d-none');
      } else {
        sr.classList.add('d-none');
      }
    });

    const emptyRow = document.getElementById('emptySearchRow');
    if (visibleCount === 0 && !Array.from(secRows).some(s => !s.classList.contains('d-none'))) {
      emptyRow.classList.remove('d-none');
    } else {
      emptyRow.classList.add('d-none');
    }
  }

  function clearSearch() {
    const input = document.getElementById('wbsSearchInput');
    if (input) {
      input.value = '';
      handleSearch('');
    }
  }

  function openAhspModal(itemId) {
    const activeProjectIdentifier = PROJECT_UUID || PROJECT_ID;
    window.location.href = `/pemetaan-ahsp?id=${encodeURIComponent(activeProjectIdentifier)}&item=${encodeURIComponent(itemId)}`;
  }

  function openEditVolumeModal(itemId) {
    const row = document.querySelector(`.wbs-item-row[data-item-id="${itemId}"]`);
    if (!row) return;

    document.getElementById('editVolumeItemId').value = itemId;
    document.getElementById('editVolumeItemTitle').textContent = row.getAttribute('data-item-name');
    document.getElementById('editVolumeInput').value = parseFloat(row.getAttribute('data-item-volume')) || 0;
    document.getElementById('editVolumeUnitSpan').textContent = row.getAttribute('data-item-unit') || 'm2';

    editVolumeModalInstance.show();
  }

  function saveVolumeEdit() {
    const itemId = document.getElementById('editVolumeItemId').value;
    const newVol = parseFloat(document.getElementById('editVolumeInput').value) || 0;
    const row = document.querySelector(`.wbs-item-row[data-item-id="${itemId}"]`);

    if (row) {
      row.setAttribute('data-item-volume', newVol);
      const volSpan = row.querySelector('.volume-text');
      if (volSpan) {
        volSpan.textContent = newVol.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
      if (typeof showToast === 'function') {
        showToast('Volume Diperbarui', `Volume pekerjaan berhasil diubah menjadi ${newVol}.`, 'success');
      }
    }
    editVolumeModalInstance.hide();
  }

  function confirmDeleteItem(itemId, itemName) {
    document.getElementById('deleteTargetItemId').value = itemId;
    document.getElementById('deleteItemModalName').textContent = `Apakah Anda yakin ingin menghapus "${itemName}" dari estimasi?`;
    deleteModalInstance.show();
  }

  function executeDeleteItem() {
    const itemId = document.getElementById('deleteTargetItemId').value;
    const row = document.querySelector(`.wbs-item-row[data-item-id="${itemId}"]`);
    if (row) {
      row.remove();
      updateTotalItemCount();
      if (typeof showToast === 'function') {
        showToast('Item Dihapus', 'Pekerjaan berhasil dihapus dari daftar.', 'warning');
      }
    }
    deleteModalInstance.hide();
  }

  function updateTotalItemCount() {
    const itemRows = document.querySelectorAll('.wbs-item-row');
    const totalEl = document.getElementById('totalItemCount');
    if (totalEl) {
      totalEl.textContent = itemRows.length;
    }
  }

  // ----------------------------------------------------------------
  // LANJUT KE RAB MODAL HANDLER (PRESERVED ORIGINAL BEHAVIOR & TOAST)
  // ----------------------------------------------------------------
  function handleLanjutKeRab() {
    const allRows = Array.from(document.querySelectorAll('.wbs-item-row'));
    const unmappedRows = allRows.filter(r => {
      const status = (r.dataset.ahspStatus || r.getAttribute('data-ahsp-status') || '').trim().toLowerCase();
      const hasWarning = (r.dataset.hasWarning || r.getAttribute('data-has-warning')) === '1';
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
      if (listContainer) {
        listContainer.innerHTML = '';
        unmappedRows.forEach(r => {
          const itemId = r.dataset.itemId || r.getAttribute('data-item-id') || '';
          const name = r.dataset.itemName || r.getAttribute('data-item-name') || '';
          const secCode = r.dataset.sectionCode || r.getAttribute('data-section-code') || '-';
          const unit = r.dataset.itemUnit || r.getAttribute('data-item-unit') || '';
          const vol = parseFloat(r.dataset.itemVolume || r.getAttribute('data-item-volume')) || 0;

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
                <p class="mb-0 fw-semibold text-truncate" style="font-size: 12px; color: #1e293b;" title="${name}">
                  ${name}
                </p>
                <div class="d-flex align-items-center gap-2 mt-0.5" style="font-size: 11px; color: #64748b;">
                  <span style="background-color: rgba(226, 232, 240, 0.8); padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 500; color: #334155;">
                    Kategori ${secCode}
                  </span>
                  <span>•</span>
                  <span>Vol: ${vol.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${unit}</span>
                </div>
              </div>
            </div>

            <a href="/pemetaan-ahsp?id=${encodeURIComponent(PROJECT_UUID || PROJECT_ID)}&item=${encodeURIComponent(itemId)}"
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
      }

      if (proceedModalInstance) proceedModalInstance.show();
    } else {
      if (unmappedSection) unmappedSection.classList.add('d-none');
      if (readySection) readySection.classList.remove('d-none');
      const readyItemCountEl = document.getElementById('proceedReadyItemCount');
      if (readyItemCountEl) readyItemCountEl.textContent = allRows.length;
      if (proceedModalInstance) proceedModalInstance.show();
    }
  }

  function confirmProceedToRab() {
    if (proceedModalInstance) proceedModalInstance.hide();
    if (typeof showToast === 'function') {
      showToast('Lanjut ke RAB', 'Menyimpan struktur estimasi dan beralih ke tahap penyusunan Rencana Anggaran Biaya (RAB)...', 'success');
    }
    setTimeout(() => {
      const activeProjectIdentifier = PROJECT_UUID || PROJECT_ID;
      window.location.href = `/rab?id=${encodeURIComponent(activeProjectIdentifier)}`;
    }, 700);
  }
</script>
<?= $this->endSection() ?>
