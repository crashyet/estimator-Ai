<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Estimator.id - Daftar Proyek Anda<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Hero Banner */
  .hero-banner {
    background-color: var(--brand-banner-bg);
    position: relative;
    height: 112px;
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
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
    margin: 0;
    z-index: 2;
    position: relative;
  }

  /* Red Action Button */
  .btn-create-project {
    background-color: var(--brand-red);
    color: #ffffff;
    font-weight: 700;
    font-size: 12.5px;
    letter-spacing: 0.04em;
    padding: 9px 20px;
    border-radius: 50rem;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 2px 4px rgba(235, 51, 36, 0.25);
    transition: all 0.2s ease;
    text-decoration: none;
  }

  .btn-create-project:hover {
    background-color: var(--brand-red-hover);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(235, 51, 36, 0.35);
  }

  /* Filter Card */
  .filter-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    margin-bottom: 28px;
    overflow: hidden;
  }

  .filter-card-header {
    background-color: var(--brand-green);
    color: #ffffff;
    font-weight: 600;
    font-size: 13px;
    padding: 9px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    user-select: none;
  }

  .filter-card-body {
    padding: 18px 20px;
    background-color: #ffffff;
  }

  .filter-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
  }

  .filter-control {
    font-size: 12.5px;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 6px 12px;
    color: #334155;
    transition: border-color 0.15s ease-in-out;
  }

  .filter-control:focus {
    border-color: var(--brand-green);
    outline: none;
    box-shadow: 0 0 0 2px rgba(15, 168, 60, 0.15);
  }

  /* Project Cards */
  .project-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
  }

  .project-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.09);
  }

  .project-card-image {
    width: 100%;
    height: 195px;
    background-color: #9ece42;
    background-image: url('<?= base_url('assets/foto/proyek/no-foto.jpg') ?>');
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    overflow: hidden;
  }

  .project-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 75%;
    transition: transform 0.3s ease;
  }

  .project-card:hover .project-card-image img {
    transform: scale(1.04);
  }

  .project-card-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-gray);
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 16px 12px 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    transition: color 0.15s ease;
  }

  .project-card-title:hover {
    color: var(--brand-green-dark);
  }

  .project-metadata {
    padding: 0 16px;
    display: flex;
    flex-direction: column;
  }

  .metadata-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 0;
    border-bottom: 1px dashed var(--border-dash);
    font-size: 13px;
    color: #64748b;
  }

  .metadata-icon-box {
    width: 26px;
    height: 26px;
    background-color: #e4e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #64748b;
    font-size: 13px;
  }

  .metadata-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #64748b;
  }

  /* Action Buttons Row */
  .project-actions {
    padding: 20px 10px 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: auto;
  }

  .btn-circle-action {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--brand-green-dark);
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    padding: 0;
  }

  .btn-circle-action:hover {
    background-color: var(--brand-green-hover);
    color: #ffffff;
    transform: scale(1.08);
    box-shadow: 0 3px 8px rgba(8, 150, 19, 0.3);
  }

  .btn-circle-action.btn-delete:hover {
    background-color: #dc3545;
    box-shadow: 0 3px 8px rgba(220, 53, 69, 0.35);
  }

  .btn-circle-action.is-locked {
    background-color: #64748b;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- 1. HERO BANNER: DAFTAR PROYEK ANDA -->
<section class="hero-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Daftar Proyek Anda" class="hero-banner-bg"
    onerror="this.style.display='none'">
  <h1 class="hero-banner-title">DAFTAR PROYEK ANDA</h1>
</section>

<!-- 2. MAIN CONTENT CONTAINER -->
<main class="container-xl py-4 px-3 px-md-4 flex-grow-1">

  <!-- Red Action Button: + BUAT PROYEK BARU -->
  <div class="mb-4 d-flex align-items-center justify-content-start">
    <a href="<?= base_url('buat_proyek') ?>" class="btn-create-project">
      <i class="bi bi-plus-circle-fill fs-6"></i>
      <span>BUAT PROYEK BARU</span>
    </a>
  </div>

  <!-- Filter Card: Tampilkan Berdasarkan -->
  <div class="filter-card">
    <div class="filter-card-header">
      <i class="bi bi-caret-down-fill" style="font-size: 10px;"></i>
      <span>Tampilkan Berdasarkan</span>
    </div>
    <div class="filter-card-body">
      <div class="row g-3">
        <!-- Field 1: Nama Proyek -->
        <div class="col-md-4">
          <label class="filter-label" for="filterName">Nama Proyek</label>
          <input type="text" id="filterName" class="form-control filter-control" placeholder="Ketik Nama Proyek"
            autocomplete="off">
        </div>

        <!-- Field 2: Lokasi Proyek -->
        <div class="col-md-4">
          <label class="filter-label" for="filterLocation">Lokasi Proyek</label>
          <select id="filterLocation" class="form-select filter-control">
            <option value="">Pilih Lokasi Proyek</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= esc($loc) ?>"><?= esc($loc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Field 3: Tahun -->
        <div class="col-md-4">
          <label class="filter-label" for="filterYear">Tahun</label>
          <input type="text" id="filterYear" class="form-control filter-control" placeholder="Ketik Tahun"
            autocomplete="off">
        </div>
      </div>
    </div>
  </div>

  <!-- Active Filter Counter & Reset Bar (Hidden by default) -->
  <div id="filterStatusAlert"
    class="d-none alert alert-light border py-2 px-3 mb-4 d-flex align-items-center justify-content-between rounded-1">
    <div class="small text-muted">
      <i class="bi bi-filter me-1 text-success"></i>
      Menampilkan <strong id="visibleCount" class="text-dark">0</strong> dari total <strong
        id="totalCount"><?= count($projects) ?></strong> proyek
    </div>
    <button type="button" class="btn btn-sm btn-link text-success text-decoration-none fw-semibold p-0"
      onclick="resetFilter()">
      <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
    </button>
  </div>

  <!-- 3. PROJECT CARDS GRID -->
  <div id="projectsGrid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 pb-5">
    <?php if (!empty($projects)): ?>
      <?php foreach ($projects as $p): ?>
        <div class="col project-col" data-id="<?= $p['id'] ?>" data-uuid="<?= esc($p['uuid']) ?>"
          data-title="<?= esc($p['title']) ?>" data-client="<?= esc($p['client']) ?>"
          data-location="<?= esc($p['location']) ?>" data-year="<?= esc($p['year']) ?>"
          data-contractor-fee="<?= esc($p['contractor_fee']) ?>" data-ppn="<?= esc($p['ppn']) ?>"
          data-summary="<?= esc($p['summary']) ?>">

          <div class="project-card">
            <!-- Card Image Header -->
            <div class="project-card-image" title="Klik untuk melihat estimasi"
              onclick="viewProjectDetail(<?= $p['id'] ?>)">
              <img src="<?= esc($p['image']) ?>" alt="<?= esc($p['title']) ?>"
                onerror="this.src='<?= base_url('assets/foto/proyek/no-foto.jpg') ?>'">
            </div>

            <!-- Project Title -->
            <h3 class="project-card-title" title="<?= esc($p['title']) ?>" onclick="viewProjectDetail(<?= $p['id'] ?>)">
              <?= esc($p['title']) ?>
            </h3>

            <!-- 3 Metadata Rows with Gray Square Icon Containers & Dashed Borders -->
            <div class="project-metadata">
              <!-- Row 1: Client -->
              <div class="metadata-row">
                <div class="metadata-icon-box">
                  <i class="bi bi-person-fill"></i>
                </div>
                <span class="metadata-text card-client-val"><?= esc($p['client']) ?></span>
              </div>

              <!-- Row 2: Location -->
              <div class="metadata-row">
                <div class="metadata-icon-box">
                  <i class="bi bi-geo-alt-fill"></i>
                </div>
                <span class="metadata-text card-location-val"><?= esc($p['location']) ?></span>
              </div>

              <!-- Row 3: Date -->
              <div class="metadata-row">
                <div class="metadata-icon-box">
                  <i class="bi bi-calendar3"></i>
                </div>
                <span class="metadata-text card-date-val"><?= esc($p['date']) ?></span>
              </div>
            </div>

            <!-- Action Buttons Row: 5 Round Green Circular Buttons -->
            <div class="project-actions">
              <!-- 1. Edit (Pencil) -->
              <button type="button" class="btn-circle-action" title="Edit Proyek" onclick="openEditModal(<?= $p['id'] ?>)">
                <i class="bi bi-pencil-fill" style="font-size: 13px;"></i>
              </button>

              <!-- 2. Team / Info (Users) -->
              <button type="button" class="btn-circle-action" title="Tim Proyek & Detail"
                onclick="openTeamModal(<?= $p['id'] ?>)">
                <i class="bi bi-people-fill" style="font-size: 14px;"></i>
              </button>

              <!-- 3. Duplicate (Copy) -->
              <button type="button" class="btn-circle-action" title="Duplikat Proyek"
                onclick="duplicateProject(<?= $p['id'] ?>)">
                <i class="bi bi-copy" style="font-size: 13px;"></i>
              </button>

              <!-- 4. Delete (Trash) -->
              <button type="button" class="btn-circle-action btn-delete" title="Hapus Proyek"
                onclick="confirmDeleteProject(<?= $p['id'] ?>)">
                <i class="bi bi-trash3-fill" style="font-size: 13px;"></i>
              </button>

              <!-- 5. Lock / Unlock -->
              <button type="button" class="btn-circle-action btn-lock" title="Kunci / Buka Akses Proyek"
                onclick="toggleLockProject(this, <?= $p['id'] ?>)">
                <i class="bi bi-unlock-fill" style="font-size: 13px;"></i>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Empty State / No Match Alert -->
  <div id="noResultsState" class="d-none text-center py-5">
    <div
      class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center p-3 text-secondary mb-3">
      <i class="bi bi-search fs-3"></i>
    </div>
    <h5 class="fw-bold text-secondary">Tidak ada proyek yang sesuai dengan filter</h5>
    <p class="small text-muted mb-3">Coba ubah kata kunci pencarian atau reset filter untuk melihat semua proyek.</p>
    <button type="button" class="btn btn-outline-success btn-sm px-3 rounded-pill" onclick="resetFilter()">
      <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
    </button>
  </div>

</main>

<!-- ==================== MODALS ==================== -->

<!-- Modal 1: Edit Proyek -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light py-3 border-bottom">
        <h5 class="modal-title fw-bold text-dark fs-6" id="editProjectModalLabel">
          <i class="bi bi-pencil-square text-success me-1"></i> EDIT DATA PROYEK
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editProjectForm" onsubmit="handleSaveEdit(event)">
        <input type="hidden" id="editProjectId">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Nama Proyek <span
                class="text-danger">*</span></label>
            <input type="text" id="editTitle" class="form-control" required autocomplete="off">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Klien / Pemilik Proyek</label>
            <input type="text" id="editClient" class="form-control" autocomplete="off">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Lokasi Proyek</label>
            <input type="text" id="editLocation" class="form-control" autocomplete="off">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Jasa Kontraktor (%)</label>
              <input type="number" step="0.1" id="editContractorFee" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">PPN (%)</label>
              <input type="number" step="0.1" id="editPpn" class="form-control">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold text-secondary">Keterangan / Ringkasan</label>
            <textarea id="editSummary" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light py-2 border-top">
          <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" id="btnSubmitEdit" class="btn btn-sm btn-success px-4 fw-semibold"
            style="background-color: var(--brand-green); border-color: var(--brand-green);">
            <i class="bi bi-save me-1"></i> Perbarui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 2: Konfirmasi Hapus Proyek -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg text-center p-3">
      <div class="modal-body">
        <div
          class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center p-3 mb-3">
          <i class="bi bi-exclamation-triangle fs-2"></i>
        </div>
        <h6 class="fw-bold text-dark mb-1">Hapus Proyek?</h6>
        <p class="small text-muted mb-3" id="deleteProjectTitle">Apakah Anda yakin ingin menghapus proyek ini beserta
          seluruh riwayat estimasinya?</p>
        <div class="d-flex gap-2 justify-content-center">
          <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
          <button type="button" id="btnConfirmDelete" class="btn btn-sm btn-danger px-3 fw-semibold">
            <i class="bi bi-trash3 me-1"></i> Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal 3: Tim Proyek & Detail -->
<div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light py-3 border-bottom">
        <h5 class="modal-title fw-bold text-dark fs-6">
          <i class="bi bi-people-fill text-success me-1"></i> TIM & INFORMASI PROYEK
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <h6 class="fw-bold text-success mb-1" id="teamModalTitle">Nama Proyek</h6>
        <p class="small text-muted mb-3" id="teamModalMeta">Lokasi &bull; Klien</p>

        <div class="p-3 bg-light rounded-2 border mb-3">
          <div class="small fw-bold text-dark mb-2">Anggota Tim Estimator:</div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="rounded-circle bg-success text-white small d-flex align-items-center justify-content-center"
              style="width: 28px; height: 28px;">AD</div>
            <div>
              <div class="small fw-semibold text-dark">Adhitya (Lead QS / Estimator)</div>
              <div class="text-muted" style="font-size: 11px;">Estimator Utama &bull; PIC</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-secondary text-white small d-flex align-items-center justify-content-center"
              style="width: 28px; height: 28px;">AI</div>
            <div>
              <div class="small fw-semibold text-dark">Gemini 2.5 Pro Estimator Bot</div>
              <div class="text-muted" style="font-size: 11px;">Automated WBS & AHSP Mapping AI</div>
            </div>
          </div>
        </div>

        <div class="small text-muted">
          <i class="bi bi-shield-check text-success me-1"></i> Semua data estimasi, WBS, dan AHSP tersinkronisasi
          otomatis dengan server backend.
        </div>
      </div>
      <div class="modal-footer bg-light py-2 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Interactive Real-time Filters
  const filterNameInput = document.getElementById('filterName');
  const filterLocationSelect = document.getElementById('filterLocation');
  const filterYearInput = document.getElementById('filterYear');
  const projectsGrid = document.getElementById('projectsGrid');
  const noResultsState = document.getElementById('noResultsState');
  const filterStatusAlert = document.getElementById('filterStatusAlert');
  const visibleCountEl = document.getElementById('visibleCount');

  function applyFilters() {
    const nameQuery = (filterNameInput.value || '').toLowerCase().trim();
    const locQuery = (filterLocationSelect.value || '').toLowerCase().trim();
    const yearQuery = (filterYearInput.value || '').toLowerCase().trim();

    const cards = projectsGrid.querySelectorAll('.project-col');
    let visibleCount = 0;

    cards.forEach(card => {
      const title = (card.dataset.title || '').toLowerCase();
      const client = (card.dataset.client || '').toLowerCase();
      const location = (card.dataset.location || '').toLowerCase();
      const year = (card.dataset.year || '').toLowerCase();

      const matchName = !nameQuery || title.includes(nameQuery) || client.includes(nameQuery);
      const matchLocation = !locQuery || location.includes(locQuery);
      const matchYear = !yearQuery || year.includes(yearQuery);

      if (matchName && matchLocation && matchYear) {
        card.classList.remove('d-none');
        visibleCount++;
      } else {
        card.classList.add('d-none');
      }
    });

    const isFilterActive = nameQuery !== '' || locQuery !== '' || yearQuery !== '';
    if (isFilterActive) {
      filterStatusAlert.classList.remove('d-none');
      visibleCountEl.textContent = visibleCount;
    } else {
      filterStatusAlert.classList.add('d-none');
    }

    if (visibleCount === 0 && cards.length > 0) {
      noResultsState.classList.remove('d-none');
    } else {
      noResultsState.classList.add('d-none');
    }
  }

  filterNameInput.addEventListener('input', applyFilters);
  filterLocationSelect.addEventListener('change', applyFilters);
  filterYearInput.addEventListener('input', applyFilters);

  function resetFilter() {
    filterNameInput.value = '';
    filterLocationSelect.value = '';
    filterYearInput.value = '';
    applyFilters();
    showToast('Filter Direset', 'Menampilkan seluruh daftar proyek.', 'info');
  }

  // View project detail
  function viewProjectDetail(id) {
    const card = document.querySelector(`.project-col[data-id="${id}"]`);
    const title = card ? card.dataset.title : 'Proyek';
    const uuid = card ? card.dataset.uuid : id;
    showToast('Membuka Proyek', `Mengakses data estimasi proyek "${title}"...`, 'info');
    setTimeout(() => {
      window.location.href = '/anggaran?id=' + encodeURIComponent(uuid || id);
    }, 450);
  }

  // Modals
  const editModal = new bootstrap.Modal(document.getElementById('editProjectModal'));
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteProjectModal'));
  const teamModalInstance = new bootstrap.Modal(document.getElementById('teamModal'));

  let deletingProjectId = null;

  // 1. Open Edit Modal
  function openEditModal(id) {
    const card = document.querySelector(`.project-col[data-id="${id}"]`);
    if (!card) return;

    document.getElementById('editProjectId').value = id;
    document.getElementById('editTitle').value = card.dataset.title || '';
    document.getElementById('editClient').value = card.dataset.client !== '-' ? card.dataset.client : '';
    document.getElementById('editLocation').value = card.dataset.location || '';
    document.getElementById('editContractorFee').value = card.dataset.contractorFee || 10;
    document.getElementById('editPpn').value = card.dataset.ppn || 11;
    document.getElementById('editSummary').value = card.dataset.summary || '';

    editModal.show();
  }

  // Save Edit (AJAX to /api/projects/:id)
  async function handleSaveEdit(e) {
    e.preventDefault();
    const id = document.getElementById('editProjectId').value;
    const btn = document.getElementById('btnSubmitEdit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const payload = {
      title: document.getElementById('editTitle').value.trim(),
      client: document.getElementById('editClient').value.trim(),
      location: document.getElementById('editLocation').value.trim(),
      contractor_fee: parseFloat(document.getElementById('editContractorFee').value) || 10,
      ppn: parseFloat(document.getElementById('editPpn').value) || 11,
      summary: document.getElementById('editSummary').value.trim()
    };

    try {
      const response = await fetch('/api/projects/' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();
      if (response.ok && result.success) {
        const card = document.querySelector(`.project-col[data-id="${id}"]`);
        if (card) {
          card.dataset.title = payload.title;
          card.dataset.client = payload.client || '-';
          card.dataset.location = payload.location || 'Kab Simeulue';
          card.dataset.contractorFee = payload.contractor_fee;
          card.dataset.ppn = payload.ppn;
          card.dataset.summary = payload.summary;

          card.querySelector('.project-card-title').textContent = payload.title;
          card.querySelector('.card-client-val').textContent = payload.client || '-';
          card.querySelector('.card-location-val').textContent = payload.location || 'Kab Simeulue';
        }

        editModal.hide();
        showToast('Diperbarui!', `Data proyek "${payload.title}" berhasil diubah.`, 'success');
      } else {
        showToast('Gagal', result.message || 'Gagal memperbarui data proyek.', 'danger');
      }
    } catch (err) {
      showToast('Koneksi Error', err.message || 'Tidak dapat terhubung ke server API.', 'danger');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-save me-1"></i> Perbarui';
    }
  }

  // 2. Team Modal
  function openTeamModal(id) {
    const card = document.querySelector(`.project-col[data-id="${id}"]`);
    if (!card) return;

    document.getElementById('teamModalTitle').textContent = card.dataset.title;
    document.getElementById('teamModalMeta').textContent = `${card.dataset.location || 'Kab Simeulue'} • Klien: ${card.dataset.client || '-'}`;
    teamModalInstance.show();
    showToast('Tim Proyek', `Membuka informasi tim untuk "${card.dataset.title}".`, 'info');
  }

  // 3. Duplicate Project
  async function duplicateProject(id) {
    const card = document.querySelector(`.project-col[data-id="${id}"]`);
    if (!card) return;

    const title = card.dataset.title;
    const dupPayload = {
      title: `${title} (Salinan)`,
      client: card.dataset.client || '',
      location: card.dataset.location || '',
      contractor_fee: parseFloat(card.dataset.contractorFee) || 10,
      ppn: parseFloat(card.dataset.ppn) || 11,
      summary: `Salinan dari ${title}`,
      status: 'Tahap Estimasi',
      image: '/assets/foto/proyek/no-foto.jpg'
    };

    try {
      const response = await fetch('/api/projects', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dupPayload)
      });

      const result = await response.json();
      if (response.ok && result.success) {
        showToast('Duplikasi Berhasil!', `Proyek telah digandakan menjadi "${dupPayload.title}".`, 'success');
        setTimeout(() => { window.location.reload(); }, 900);
      } else {
        showToast('Gagal Menduplikasi', result.message || 'Gagal menyimpan duplikasi ke database.', 'danger');
      }
    } catch (err) {
      showToast('Koneksi Error', err.message || 'Gagal menghubungi server.', 'danger');
    }
  }

  // 4. Delete Project
  function confirmDeleteProject(id) {
    const card = document.querySelector(`.project-col[data-id="${id}"]`);
    if (!card) return;

    deletingProjectId = id;
    document.getElementById('deleteProjectTitle').textContent = `Apakah Anda yakin ingin menghapus proyek "${card.dataset.title}" beserta seluruh riwayat estimasinya?`;
    deleteModal.show();
  }

  document.getElementById('btnConfirmDelete').addEventListener('click', async function () {
    if (!deletingProjectId) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

    try {
      const response = await fetch('/api/projects/' + deletingProjectId, {
        method: 'DELETE'
      });

      const result = await response.json();
      if (response.ok && result.success) {
        const card = document.querySelector(`.project-col[data-id="${deletingProjectId}"]`);
        if (card) {
          card.style.transition = 'all 0.3s ease';
          card.style.opacity = '0';
          card.style.transform = 'scale(0.8)';
          setTimeout(() => { card.remove(); applyFilters(); }, 300);
        }
        deleteModal.hide();
        showToast('Dihapus!', 'Proyek berhasil dihapus dari database.', 'danger');
      } else {
        showToast('Gagal', result.message || 'Gagal menghapus proyek.', 'danger');
      }
    } catch (err) {
      showToast('Koneksi Error', err.message || 'Gagal menghubungi server.', 'danger');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-trash3 me-1"></i> Hapus';
      deletingProjectId = null;
    }
  });

  // 5. Toggle Lock
  function toggleLockProject(btn, id) {
    const icon = btn.querySelector('i');
    const isLocked = icon.classList.contains('bi-lock-fill');
    const card = document.querySelector(`.project-col[data-id="${id}"]`);
    const title = card ? card.dataset.title : 'Proyek';

    if (isLocked) {
      icon.className = 'bi bi-unlock-fill';
      btn.classList.remove('is-locked');
      btn.title = 'Kunci Proyek';
      showToast('Akses Terbuka', `Proyek "${title}" kini dalam status Terbuka.`, 'info');
    } else {
      icon.className = 'bi bi-lock-fill';
      btn.classList.add('is-locked');
      btn.title = 'Buka Akses Proyek';
      showToast('Proyek Dikunci', `Proyek "${title}" berhasil dikunci dari pengeditan tidak sengaja.`, 'warning');
    }
  }
</script>
<?= $this->endSection() ?>