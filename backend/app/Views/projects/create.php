<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Estimator.id - Proyek Baru<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --btn-submit-green: #2e7d32;
    --btn-submit-hover: #1b5e20;
    --btn-cancel-yellow: #fbc02d;
    --btn-cancel-hover: #f57f17;
    --btn-doc-green: #7cb342;
    --btn-doc-hover: #689f38;
    --card-border: #e2e8f0;
  }

  /* Hero Banner: PROYEK BARU */
  .hero-banner {
    background-color: var(--brand-banner-bg);
    position: relative;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: inset 0 -2px 6px rgba(0, 0, 0, 0.05);
  }
  .hero-banner-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-position: center right;
    pointer-events: none;
    z-index: 1;
  }
  .hero-banner-title {
    color: #ffffff;
    font-weight: 800;
    font-size: 26px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-shadow: 0 1px 3px rgba(0,0,0,0.18);
    margin: 0;
    z-index: 2;
    position: relative;
  }

  /* Main Workspace Card */
  .workspace-card {
    background-color: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 50px;
  }
  .workspace-header-bar {
    background-color: #f0f8ed;
    border-bottom: 1px solid #dcf0d6;
    padding: 12px 20px;
    text-align: center;
  }
  .workspace-header-title {
    font-size: 14px;
    font-weight: 700;
    color: #334155;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin: 0;
  }
  .workspace-header-title span {
    color: #64748b;
    font-weight: 600;
  }

  /* Project Photo Container */
  .photo-preview-container {
    width: 100%;
    max-width: 320px;
    aspect-ratio: 1 / 1;
    background-color: #9ece42;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  }
  .photo-preview-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 75%;
    transition: transform 0.3s ease;
  }
  .photo-floating-actions {
    position: absolute;
    bottom: 16px;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    z-index: 10;
  }
  .btn-photo-action {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background-color: var(--brand-green-dark);
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .btn-photo-action:hover {
    transform: scale(1.1);
    background-color: var(--brand-green-hover);
    color: #ffffff;
  }
  .btn-photo-action.btn-photo-delete:hover {
    background-color: #dc3545;
  }

  /* Add Document Button */
  .btn-add-doc {
    background-color: var(--btn-doc-green);
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 8px 18px;
    border-radius: 3px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .btn-add-doc:hover {
    background-color: var(--btn-doc-hover);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  }

  /* Documents Box */
  .doc-box {
    width: 100%;
    max-width: 320px;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    background-color: #ffffff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  }
  .doc-box-header {
    background-color: #f0f8ed;
    border-bottom: 1px solid #dcf0d6;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
  }
  .doc-box-body {
    padding: 16px;
    min-height: 150px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
  .doc-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 10px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    width: 100%;
    margin-bottom: 6px;
    font-size: 12px;
  }

  /* Form Fields */
  .form-field-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    display: block;
  }
  .form-field-label span.req {
    font-size: 11px;
    font-weight: 400;
    color: #94a3b8;
  }
  .custom-input {
    font-size: 12.5px;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 8px 12px;
    color: #1e293b;
    background-color: #ffffff;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  .custom-input:focus {
    border-color: var(--brand-green);
    outline: none;
    box-shadow: 0 0 0 2px rgba(15, 168, 60, 0.18);
  }

  /* Bottom Action Buttons */
  .btn-action-submit {
    background-color: var(--btn-submit-green);
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 24px;
    border-radius: 3px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    transition: all 0.2s ease;
    cursor: pointer;
  }
  .btn-action-submit:hover {
    background-color: var(--btn-submit-hover);
    color: #ffffff;
    box-shadow: 0 3px 8px rgba(27, 94, 32, 0.3);
  }
  .btn-action-cancel {
    background-color: var(--btn-cancel-yellow);
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 24px;
    border-radius: 3px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    transition: all 0.2s ease;
    text-decoration: none;
  }
  .btn-action-cancel:hover {
    background-color: var(--btn-cancel-hover);
    color: #ffffff;
    box-shadow: 0 3px 8px rgba(245, 127, 23, 0.3);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero Banner: PROYEK BARU -->
<section class="hero-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Proyek Baru" class="hero-banner-bg" onerror="this.style.display='none'">
  <h1 class="hero-banner-title">PROYEK BARU</h1>
</section>

<!-- Main Form Container -->
<main class="container-xl py-4 px-3 px-md-4 flex-grow-1">
  <div class="workspace-card">
    
    <!-- Subtitle Header Bar: LENGKAPI PROFIL PROYEK -->
    <div class="workspace-header-bar">
      <h2 class="workspace-header-title">
        LENGKAPI PROFIL <span>PROYEK</span>
      </h2>
    </div>

    <!-- Main Form Body -->
    <form id="createProjectForm" onsubmit="handleFormSubmit(event)" class="p-4 p-md-5">
      <div class="row g-4 g-lg-5">
        
        <!-- LEFT COLUMN: Foto Proyek & Dokumen -->
        <div class="col-lg-5 col-xl-4 d-flex flex-column align-items-center gap-3">
          
          <!-- 1. Foto Proyek Container -->
          <div class="photo-preview-container">
            <img id="projectPhotoPreview" 
                 src="<?= base_url('assets/foto/proyek/no-foto.jpg') ?>" 
                 alt="Foto Proyek" 
                 onerror="this.src='<?= base_url('assets/foto/proyek/no-foto.jpg') ?>'">
            
            <!-- Floating Buttons: Trash (Reset) & Pencil (Upload) -->
            <div class="photo-floating-actions">
              <!-- Button Reset Foto -->
              <button type="button" class="btn-photo-action btn-photo-delete" title="Hapus / Reset Foto" onclick="handlePhotoReset()">
                <i class="bi bi-trash3-fill" style="font-size: 13px;"></i>
              </button>
              <!-- Button Upload Foto -->
              <button type="button" class="btn-photo-action" title="Ganti Foto Proyek" onclick="triggerPhotoUpload()">
                <i class="bi bi-pencil-fill" style="font-size: 13px;"></i>
              </button>
            </div>

            <!-- Hidden File Input for Photo -->
            <input type="file" id="photoFileInput" accept="image/*" class="d-none" onchange="handlePhotoSelected(event)">
          </div>

          <!-- 2. Tombol TAMBAH DOKUMEN -->
          <div class="w-100 d-flex justify-content-center">
            <button type="button" class="btn-add-doc" onclick="triggerDocUpload()">
              <i class="bi bi-file-earmark-plus-fill fs-6"></i>
              <span>TAMBAH DOKUMEN</span>
            </button>
            <!-- Hidden File Input for Documents -->
            <input type="file" id="docFileInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.dwg,.ifc,.rvt,image/*" class="d-none" onchange="handleDocsSelected(event)">
          </div>

          <!-- 3. Box Daftar Dokumen -->
          <div class="doc-box">
            <div class="doc-box-header">
              Daftar Dokumen
            </div>
            <div class="doc-box-body" id="docListContainer">
              <!-- Empty State -->
              <div id="docEmptyState" class="text-center py-2">
                <img src="<?= base_url('assets/img/not-found.png') ?>" alt="Tidak ada dokumen" style="width: 80px; height: 80px; object-fit: contain; opacity: 0.85;" onerror="this.style.display='none'">
                <div class="text-muted small mt-2 fw-medium" style="font-size: 12px;">Tidak ada dokumen</div>
              </div>
              <!-- Document List Items (Dynamic) -->
              <div id="docItemsList" class="w-100 d-none"></div>
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: Form Inputs -->
        <div class="col-lg-7 col-xl-8 d-flex flex-column gap-3">
          
          <!-- Field 1: Nama Proyek -->
          <div>
            <label class="form-field-label" for="inputTitle">
              Nama Proyek <span class="req">(wajib diisi)</span>
            </label>
            <input type="text" id="inputTitle" name="title" class="form-control custom-input" required autocomplete="off">
          </div>

          <!-- Field 2: Lokasi Proyek -->
          <div>
            <label class="form-field-label" for="inputLocation">
              Lokasi Proyek <span class="req">(wajib diisi)</span>
            </label>
            <select id="inputLocation" name="location" class="form-select custom-input" required>
              <option value="">Pilih Lokasi Proyek</option>
              <?php foreach ($locations as $loc): ?>
                <option value="<?= esc($loc) ?>"><?= esc($loc) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Field 3: Pemilik Proyek -->
          <div>
            <label class="form-field-label" for="inputClient">
              Pemilik Proyek <span class="req">(wajib diisi)</span>
            </label>
            <input type="text" id="inputClient" name="client" class="form-control custom-input" required autocomplete="off">
          </div>

          <!-- Field 4: Jasa Kontraktor -->
          <div>
            <label class="form-field-label" for="inputContractorFee">
              Jasa Kontraktor <span class="req">(wajib diisi)</span>
            </label>
            <div class="input-group">
              <input type="text" id="inputContractorFee" name="contractor_fee" class="form-control custom-input text-end" value="10.00" required>
              <span class="input-group-text bg-white border-start-0 text-muted small fw-medium">%</span>
            </div>
          </div>

          <!-- Field 5: PPN -->
          <div>
            <label class="form-field-label" for="inputPpn">
              PPN <span class="req">(wajib diisi)</span>
            </label>
            <div class="input-group">
              <input type="text" id="inputPpn" name="ppn" class="form-control custom-input text-end" value="11.00" required>
              <span class="input-group-text bg-white border-start-0 text-muted small fw-medium">%</span>
            </div>
          </div>

          <!-- Field 6: Keterangan Lain -->
          <div>
            <label class="form-field-label" for="inputSummary">
              Keterangan Lain
            </label>
            <textarea id="inputSummary" name="summary" class="form-control custom-input" rows="4"></textarea>
          </div>

        </div>
      </div>

      <!-- BOTTOM ACTION BUTTONS: Centered -->
      <div class="d-flex align-items-center justify-content-center gap-3 mt-5 pt-4 border-top">
        <!-- Button 1: SIMPAN & LANJUTKAN -->
        <button type="submit" id="btnSubmitForm" class="btn-action-submit">
          <i class="bi bi-check2 fs-6"></i>
          <span>SIMPAN & LANJUTKAN</span>
        </button>

        <!-- Button 2: BATAL -->
        <a href="<?= base_url('proyek') ?>" class="btn-action-cancel">
          <i class="bi bi-x fs-6"></i>
          <span>BATAL</span>
        </a>
      </div>
    </form>
  </div>
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // 1. Photo Upload Handlers
  const defaultPhotoSrc = '<?= base_url('assets/foto/proyek/no-foto.jpg') ?>';
  let currentPhotoBase64 = null;

  function triggerPhotoUpload() {
    document.getElementById('photoFileInput').click();
  }

  function handlePhotoSelected(event) {
    const file = event.target.files && event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      showToast('Format Tidak Didukung', 'Silakan pilih file berupa gambar (JPEG, PNG, dll).', 'warning');
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      currentPhotoBase64 = e.target.result;
      document.getElementById('projectPhotoPreview').src = currentPhotoBase64;
      showToast('Foto Dipilih', `Foto proyek "${file.name}" berhasil diunggah ke preview.`, 'success');
    };
    reader.readAsDataURL(file);
  }

  function handlePhotoReset() {
    currentPhotoBase64 = null;
    document.getElementById('projectPhotoPreview').src = defaultPhotoSrc;
    document.getElementById('photoFileInput').value = '';
    showToast('Foto Direset', 'Foto proyek dikembalikan ke gambar default.', 'info');
  }

  // 2. Documents Upload Handlers
  let uploadedDocuments = [];

  function triggerDocUpload() {
    document.getElementById('docFileInput').click();
  }

  function handleDocsSelected(event) {
    const files = Array.from(event.target.files || []);
    if (files.length === 0) return;

    files.forEach(f => {
      const sizeMb = (f.size / (1024 * 1024)).toFixed(2) + ' MB';
      uploadedDocuments.push({
        id: 'doc-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5),
        name: f.name,
        size: sizeMb
      });
    });

    renderDocumentList();
    showToast('Dokumen Ditambahkan', `${files.length} dokumen berhasil ditambahkan ke daftar.`, 'success');
    event.target.value = '';
  }

  function removeDocument(id) {
    const doc = uploadedDocuments.find(d => d.id === id);
    const name = doc ? doc.name : 'Dokumen';
    uploadedDocuments = uploadedDocuments.filter(d => d.id !== id);
    renderDocumentList();
    showToast('Dokumen Dihapus', `"${name}" telah dihapus dari daftar dokumen.`, 'info');
  }

  function renderDocumentList() {
    const emptyState = document.getElementById('docEmptyState');
    const itemsList = document.getElementById('docItemsList');

    if (uploadedDocuments.length === 0) {
      emptyState.classList.remove('d-none');
      itemsList.classList.add('d-none');
      itemsList.innerHTML = '';
    } else {
      emptyState.classList.add('d-none');
      itemsList.classList.remove('d-none');
      itemsList.innerHTML = uploadedDocuments.map(doc => `
        <div class="doc-item">
          <div class="d-flex align-items-center gap-2 text-truncate">
            <i class="bi bi-file-earmark-text-fill text-success fs-6 flex-shrink-0"></i>
            <div class="text-truncate">
              <div class="fw-semibold text-dark text-truncate" style="font-size: 11.5px;">${doc.name}</div>
              <div class="text-muted" style="font-size: 10px;">${doc.size}</div>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 fw-bold" onclick="removeDocument('${doc.id}')" title="Hapus dokumen">
            <i class="bi bi-x-lg" style="font-size: 11px;"></i>
          </button>
        </div>
      `).join('');
    }
  }

  // 3. Form Submit Handler (AJAX POST to /api/projects)
  async function handleFormSubmit(event) {
    event.preventDefault();

    const title = document.getElementById('inputTitle').value.trim();
    const location = document.getElementById('inputLocation').value.trim();
    const client = document.getElementById('inputClient').value.trim();
    const feeRaw = document.getElementById('inputContractorFee').value.replace(',', '.');
    const ppnRaw = document.getElementById('inputPpn').value.replace(',', '.');
    const summary = document.getElementById('inputSummary').value.trim();

    if (!title) {
      showToast('Validasi Gagal', 'Nama Proyek wajib diisi!', 'danger');
      document.getElementById('inputTitle').focus();
      return;
    }
    if (!location) {
      showToast('Validasi Gagal', 'Lokasi Proyek wajib dipilih!', 'danger');
      document.getElementById('inputLocation').focus();
      return;
    }
    if (!client) {
      showToast('Validasi Gagal', 'Pemilik Proyek wajib diisi!', 'danger');
      document.getElementById('inputClient').focus();
      return;
    }

    const contractorFee = parseFloat(feeRaw) || 10.0;
    const ppn = parseFloat(ppnRaw) || 11.0;

    const btn = document.getElementById('btnSubmitForm');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const payload = {
      title: title,
      client: client,
      location: location,
      contractor_fee: contractorFee,
      ppn: ppn,
      summary: summary || 'Proyek Baru dibuat melalui formulir profil proyek.',
      status: 'Tahap Estimasi',
      image: currentPhotoBase64 || defaultPhotoSrc
    };

    try {
      const response = await fetch('<?= base_url('api/projects') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();
      if (response.ok && result.success) {
        showToast('Proyek Berhasil Dibuat!', `Proyek "${title}" berhasil disimpan ke sistem. Mengalihkan ke tahap deteksi anggaran...`, 'success');
        const projId = result.data?.uuid || result.data?.id;
        setTimeout(() => {
          window.location.href = '<?= base_url('anggaran') ?>?id=' + encodeURIComponent(projId);
        }, 1000);
      } else {
        showToast('Gagal Menyimpan', result.message || 'Terjadi kesalahan pada server saat membuat proyek.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 fs-6"></i> <span>SIMPAN & LANJUTKAN</span>';
      }
    } catch (err) {
      showToast('Kesalahan Koneksi', err.message || 'Gagal menghubungi server API.', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2 fs-6"></i> <span>SIMPAN & LANJUTKAN</span>';
    }
  }
</script>
<?= $this->endSection() ?>
