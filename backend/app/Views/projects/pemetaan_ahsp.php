<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Pemetaan Item Pekerjaan AHSP - <?= esc($project['title']) ?> | Estimator.id
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Banner Header */
  .ahsp-banner {
    position: relative;
    width: 100%;
    background-color: #79bf39;
    background: linear-gradient(135deg, #74b836 0%, #88c946 50%, #68a82d 100%);
    padding: 38px 20px 48px 20px;
    text-align: center;
    overflow: hidden;
    box-shadow: inset 0 -2px 6px rgba(0, 0, 0, 0.04);
  }
  .ahsp-banner-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-position: center right;
    pointer-events: none;
    opacity: 0.95;
  }
  .ahsp-banner-title {
    position: relative;
    z-index: 2;
    color: #ffffff;
    font-size: 26px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
  }

  /* Main Card Workspace */
  .ahsp-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    padding: 24px 28px;
    margin: 24px auto 50px auto;
  }

  /* Target Item Green Box */
  .ahsp-target-box {
    background-color: #009624;
    color: #ffffff;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 150, 36, 0.15);
  }
  .ahsp-target-title {
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 4px;
    color: #ffffff;
  }
  .ahsp-target-meta {
    font-size: 13px;
    color: #dcfce7;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Table Design */
  .ahsp-table-container {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    background-color: #ffffff;
  }
  .ahsp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    margin-bottom: 0;
  }
  .ahsp-table thead th {
    background-color: #089613;
    color: #ffffff;
    font-weight: 700;
    font-size: 12.5px;
    letter-spacing: 0.02em;
    padding: 12px 16px;
    border: none;
    white-space: nowrap;
  }
  .ahsp-table tbody tr {
    transition: background-color 0.15s ease;
  }
  .ahsp-table tbody tr:nth-child(even) {
    background-color: #fbfdfc;
  }
  .ahsp-table tbody tr:hover {
    background-color: #f1faf2;
  }
  .ahsp-table tbody td {
    padding: 11px 16px;
    border-top: 1px solid #f1f5f9;
    vertical-align: middle;
  }

  /* Buttons */
  .btn-select-ahsp {
    background-color: #009624;
    border: 1px solid #009624;
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 1px 3px rgba(0, 150, 36, 0.2);
  }
  .btn-select-ahsp:hover {
    background-color: #007b1e;
    border-color: #007b1e;
    color: #ffffff;
    transform: translateY(-1px);
  }
  .btn-select-outline {
    background-color: #ffffff;
    border: 1px solid #009624;
    color: #009624;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .btn-select-outline:hover {
    background-color: #009624;
    color: #ffffff;
  }

  /* Suggestion Info Box */
  .ahsp-suggestion-box {
    background-color: #f2faf3;
    border: 1px solid #d3ead6;
    border-radius: 12px;
    padding: 24px 20px;
    text-align: center;
    margin: 28px 0;
  }
  .info-icon-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: #009624;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 18px;
    box-shadow: 0 2px 6px rgba(0, 150, 36, 0.2);
  }

  /* Master Data Table Scroll Area */
  .master-table-scroll {
    max-height: 480px;
    overflow-y: auto;
    position: relative;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }
  .master-table-scroll::-webkit-scrollbar {
    width: 7px;
  }
  .master-table-scroll::-webkit-scrollbar-track {
    background: #f8fafc;
  }
  .master-table-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
  .master-table-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 10;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Banner with Green Pattern -->
<div class="ahsp-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Pemetaan AHSP" class="ahsp-banner-bg" onerror="this.style.display='none'">
  <h1 class="ahsp-banner-title">PEMETAAN ITEM PEKERJAAN AHSP</h1>
</div>

<!-- Main Container -->
<main class="container-fluid px-3 px-md-5 my-3" style="max-width: 1360px;">
  <div class="ahsp-card">

    <!-- Top Bar: Breadcrumb + Back Button -->
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 pb-3 mb-3 border-bottom border-light-subtle">
      <div>
        <nav aria-label="breadcrumb" class="mb-1">
          <ol class="breadcrumb mb-0" style="font-size: 12px;">
            <li class="breadcrumb-item"><a href="<?= base_url('proyek') ?>" class="text-secondary text-decoration-none">Proyek</a></li>
            <li class="breadcrumb-item"><a href="<?= esc($returnUrl) ?>" class="text-secondary text-decoration-none">Estimasi RAB</a></li>
            <li class="breadcrumb-item active text-success fw-bold" aria-current="page">Pemetaan Pekerjaan</li>
          </ol>
        </nav>
        <h2 class="fw-bold text-dark fs-5 mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-book text-success"></i>
          <span>Pemetaan Item Pekerjaan AHSP</span>
        </h2>
      </div>

      <a href="<?= esc($returnUrl) ?>" class="btn btn-light border border-secondary-subtle px-3 py-1.5 rounded-pill text-secondary fw-bold d-inline-flex align-items-center gap-1 text-decoration-none" style="font-size: 12.5px;">
        <i class="bi bi-chevron-left"></i>
        <span>Kembali</span>
      </a>
    </div>

    <?php if ($targetItem): ?>
      <!-- Target Item Green Summary Box -->
      <div class="ahsp-target-box">
        <h3 class="ahsp-target-title"><?= esc($targetItem['ahsp_name'] ?: $targetItem['item_name']) ?></h3>
        <div class="ahsp-target-meta">
          <span>Satuan: <strong class="text-white"><?= esc($targetItem['ahsp_unit'] ?: $targetItem['unit']) ?></strong></span>
          <span>•</span>
          <span>Hasil Deteksi: <strong class="text-warning fw-bold"><?= esc($targetItem['item_name']) ?></strong></span>
        </div>
      </div>

      <!-- SECTION 1: REKOMENDASI ITEM PEKERJAAN -->
      <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between pb-2 mb-2.5 border-bottom border-light-subtle">
          <h3 class="fw-bold text-dark text-uppercase d-flex align-items-center gap-2 mb-0" style="font-size: 13.5px; letter-spacing: 0.03em;">
            <i class="bi bi-book text-success"></i>
            <span>Rekomendasi Item Pekerjaan</span>
          </h3>
          <?php if (!empty($candidates)): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 11.5px;">
              <?= count($candidates) ?> Pilihan Ditemukan
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($candidates)): ?>
          <div class="ahsp-table-container">
            <table class="ahsp-table">
              <thead>
                <tr>
                  <th style="width: 55px;" class="text-center">No.</th>
                  <th>Uraian Pekerjaan Standar AHSP</th>
                  <th style="width: 90px;" class="text-center">Satuan</th>
                  <th style="width: 100px;" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($candidates as $idx => $cand): ?>
                  <tr>
                    <td class="text-center text-muted font-medium"><?= $idx + 1 ?></td>
                    <td class="fw-semibold text-dark"><?= esc($cand['nama_pekerjaan']) ?></td>
                    <td class="text-center text-secondary"><?= esc($cand['satuan']) ?></td>
                    <td class="text-center">
                      <button 
                        type="button" 
                        class="btn-select-ahsp"
                        onclick='openConfirmModal(<?= json_encode([
                          "code"   => $cand["id_pekerjaan"],
                          "name"   => $cand["nama_pekerjaan"],
                          "unit"   => $cand["satuan"],
                          "score"  => $cand["score"] ?? 1.0,
                        ]) ?>)'
                      >
                        Pilih
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="py-4 text-center bg-light rounded-3 border border-dashed text-secondary small">
            Belum ada rekomendasi pilihan item pekerjaan untuk item ini.
          </div>
        <?php endif; ?>
      </div>

      <!-- SECTION 2: SUGGESTION BANNER -->
      <div class="ahsp-suggestion-box">
        <div class="info-icon-circle">
          <i class="bi bi-info-lg"></i>
        </div>
        <p class="text-dark fw-semibold mx-auto mb-3" style="max-width: 620px; font-size: 13.5px; line-height: 1.6;">
          Apakah hasil rekomendasi di atas belum sesuai? Apabila ingin menemukan item pekerjaan yang lebih tepat, silakan dapat menelusuri daftar item lainnya pada koleksi AHSP.
        </p>
        <button 
          type="button" 
          id="btnToggleMaster" 
          class="btn btn-success rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2"
          style="background-color: #009624; border-color: #009624; font-size: 13px; box-shadow: 0 4px 12px rgba(0, 150, 36, 0.25);"
          onclick="toggleMasterCollection()"
        >
          <i class="bi bi-book"></i>
          <span id="btnToggleMasterText">Telusuri Koleksi Master Data AHSP</span>
          <i class="bi bi-chevron-down" id="btnToggleMasterIcon"></i>
        </button>
      </div>

      <!-- SECTION 3: KOLEKSI DATA PEKERJAAN AHSP (COLLAPSIBLE) -->
      <div id="masterCollectionSection" class="d-none">
        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom border-light-subtle">
          <h3 class="fw-bold text-dark text-uppercase d-flex align-items-center gap-2 mb-0" style="font-size: 13.5px; letter-spacing: 0.03em;">
            <i class="bi bi-book text-success"></i>
            <span>Koleksi Data Pekerjaan AHSP</span>
          </h3>
        </div>

        <!-- Search Bar -->
        <div class="d-flex gap-2 mb-3">
          <div class="position-relative flex-grow-1">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
            <input 
              type="text" 
              id="masterSearchInput" 
              class="form-control rounded-3 ps-5 pe-5 py-2" 
              placeholder="Cari nama item pekerjaan (misal: Pasangan Dinding Bata Merah)..."
              style="font-size: 13px;"
              onkeydown="if(event.key==='Enter') searchMasterAhsp()"
              oninput="handleSearchInputChange(this.value)"
            >
            <button 
              type="button" 
              id="clearMasterSearchBtn" 
              class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0 text-secondary border-0 d-none" 
              onclick="clearMasterSearch()" 
              title="Hapus pencarian"
              style="font-size: 16px;"
            >
              &times;
            </button>
          </div>

          <button 
            type="button" 
            id="btnSearchMaster" 
            class="btn btn-success fw-bold px-4 rounded-3 d-inline-flex align-items-center gap-1.5"
            style="background-color: #009624; border-color: #009624; font-size: 13px; white-space: nowrap;"
            onclick="searchMasterAhsp()"
          >
            Cari Data
          </button>

          <button 
            type="button" 
            id="btnResetMaster" 
            class="btn btn-light border fw-semibold px-3 rounded-3 d-none"
            style="font-size: 13px; white-space: nowrap;"
            onclick="clearMasterSearch()"
          >
            Reset
          </button>
        </div>

        <!-- Master AHSP Table Container -->
        <div class="master-table-scroll" id="masterScrollContainer" onscroll="handleMasterScroll(event)">
          <table class="ahsp-table">
            <thead>
              <tr>
                <th style="width: 55px;" class="text-center">NO.</th>
                <th>URAIAN PEKERJAAN STANDAR AHSP</th>
                <th style="width: 90px;" class="text-center">SATUAN</th>
                <th style="width: 100px;" class="text-center">AKSI</th>
              </tr>
            </thead>
            <tbody id="masterAhspTableBody">
              <tr>
                <td colspan="4" class="py-5 text-center text-muted font-medium">
                  Database pekerjaan AHSP kosong.
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Bottom Status / Loading -->
          <div id="masterLoadingIndicator" class="py-3 text-center text-success small fw-bold bg-light border-top d-none">
            <div class="spinner-border spinner-border-sm me-2 text-success" role="status"></div>
            <span>Memuat data AHSP dari server database...</span>
          </div>

          <div 
            id="btnLoadMoreMaster" 
            class="py-2.5 text-center text-success small fw-bold bg-light border-top cursor-pointer d-none" 
            style="cursor: pointer;"
            onclick="loadNextMasterPage()"
          >
            ▼ Gulir ke bawah atau klik di sini untuk memuat data berikutnya (+50 item)
          </div>

          <div id="masterAllLoadedText" class="py-2.5 text-center text-muted small fw-semibold bg-light border-top d-none">
            ✓ Seluruh data pekerjaan telah dimuat
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="alert alert-warning rounded-3 border-0 py-4 text-center">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-warning mb-2 d-block"></i>
        <h5 class="fw-bold">Item Pekerjaan Tidak Ditemukan</h5>
        <p class="text-secondary small mb-3">Tidak ada item pekerjaan yang dipilih atau data estimasi belum tersedia.</p>
        <a href="<?= esc($returnUrl) ?>" class="btn btn-success btn-sm rounded-pill px-4">Kembali ke Anggaran</a>
      </div>
    <?php endif; ?>

  </div>
</main>

<!-- ============================================================== -->
<!-- CONFIRMATION MODAL                                             -->
<!-- ============================================================== -->
<div class="modal fade" id="ahspConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
    <div class="modal-content border-0 rounded-4 shadow-lg p-3">
      <div class="modal-body p-3">
        
        <!-- Header with Book Icon -->
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background-color: #f1faf2; border: 1px solid #cbeed0; color: #009624;">
            <i class="bi bi-book fs-5"></i>
          </div>
          <div>
            <h5 class="fw-bold text-dark fs-6 mb-0.5">Konfirmasi Pemetaan Pekerjaan</h5>
            <p class="text-muted small mb-0" style="font-size: 12px;">Apakah Anda yakin ingin memilih pekerjaan ini?</p>
          </div>
        </div>

        <!-- Target vs Selected Item Summary Box -->
        <div class="bg-light rounded-3 p-3 border mb-3" style="font-size: 12.5px;">
          <div class="mb-2 pb-2 border-bottom">
            <span class="text-muted small text-uppercase fw-bold d-block mb-1" style="font-size: 10.5px;">Target Pekerjaan RAB:</span>
            <p class="fw-semibold text-dark mb-0 leading-snug" id="confirmModalTargetName">
              <?= esc($targetItem['name'] ?? '-') ?>
            </p>
          </div>
          <div>
            <span class="text-success small text-uppercase fw-bold d-block mb-1" style="font-size: 10.5px;">Pekerjaan AHSP Dipilih:</span>
            <p class="fw-bold text-dark mb-1 leading-snug" id="confirmModalSelectedName">
              -
            </p>
            <span class="text-muted small d-block">
              Satuan: <strong class="text-dark" id="confirmModalSelectedUnit">-</strong>
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex align-items-center justify-content-end gap-2 pt-1">
          <button type="button" class="btn btn-light border rounded-pill px-3 py-1.5 text-secondary fw-semibold" style="font-size: 12.5px;" data-bs-dismiss="modal">
            Batal
          </button>
          <button type="button" id="btnConfirmApply" class="btn btn-success rounded-pill px-4 py-1.5 fw-bold" style="background-color: #009624; border-color: #009624; font-size: 12.5px;" onclick="applyConfirmedAhsp()">
            Ya, Simpan Pemetaan
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  const PROJECT_UUID = "<?= esc($project['uuid'] ?: $project['id']) ?>";
  const TARGET_ITEM_ID = "<?= esc($targetItem['id'] ?? '') ?>";
  const RETURN_URL = "<?= esc($returnUrl) ?>";

  let showMaster = false;
  let masterItems = [];
  let masterPage = 1;
  let masterTotalPages = 1;
  let masterTotalCount = 0;
  let masterSearchQuery = '';
  let isLoadingMaster = false;

  let selectedCandidate = null;
  let confirmModalInstance = null;

  document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('ahspConfirmModal');
    if (modalEl) {
      confirmModalInstance = new bootstrap.Modal(modalEl);
    }
  });

  // Toggle Master AHSP Section
  function toggleMasterCollection() {
    showMaster = !showMaster;
    const sec = document.getElementById('masterCollectionSection');
    const textEl = document.getElementById('btnToggleMasterText');
    const iconEl = document.getElementById('btnToggleMasterIcon');

    if (showMaster) {
      sec.classList.remove('d-none');
      textEl.textContent = 'Sembunyikan Koleksi Master Data AHSP';
      iconEl.className = 'bi bi-chevron-up';
      if (masterItems.length === 0) {
        fetchMasterAhsp(1, true);
      }
      setTimeout(() => {
        sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    } else {
      sec.classList.add('d-none');
      textEl.textContent = 'Telusuri Koleksi Master Data AHSP';
      iconEl.className = 'bi bi-chevron-down';
    }
  }

  // Fetch Master AHSP Data
  async function fetchMasterAhsp(page = 1, isReset = false) {
    if (isLoadingMaster) return;
    isLoadingMaster = true;

    const loadingInd = document.getElementById('masterLoadingIndicator');
    const btnLoadMore = document.getElementById('btnLoadMoreMaster');
    const allLoaded = document.getElementById('masterAllLoadedText');
    const tbody = document.getElementById('masterAhspTableBody');

    if (loadingInd) loadingInd.classList.remove('d-none');
    if (btnLoadMore) btnLoadMore.classList.add('d-none');
    if (allLoaded) allLoaded.classList.add('d-none');

    try {
      const url = `<?= base_url('api/ahsp/list') ?>?page=${page}&limit=50&search=${encodeURIComponent(masterSearchQuery.trim())}`;
      const res = await fetch(url);
      const data = await res.json();

      const items = data.items || [];
      masterTotalCount = data.total || 0;
      masterTotalPages = data.total_pages || 1;
      masterPage = page;

      if (isReset) {
        masterItems = items;
        tbody.innerHTML = '';
      } else {
        masterItems = masterItems.concat(items);
      }

      renderMasterRows(items, isReset);

      if (masterPage < masterTotalPages) {
        if (btnLoadMore) btnLoadMore.classList.remove('d-none');
      } else if (masterItems.length > 0) {
        if (allLoaded) {
          allLoaded.classList.remove('d-none');
          allLoaded.textContent = `✓ Seluruh data (${masterTotalCount.toLocaleString('id-ID')} item AHSP) telah dimuat`;
        }
      }

    } catch (err) {
      console.error('Error fetching master AHSP data:', err);
      if (isReset && tbody) {
        tbody.innerHTML = `
          <tr>
            <td colspan="4" class="py-5 text-center text-danger small">
              Gagal memuat data dari server database AHSP.
            </td>
          </tr>
        `;
      }
    } finally {
      isLoadingMaster = false;
      if (loadingInd) loadingInd.classList.add('d-none');
    }
  }

  function renderMasterRows(newItems, isReset) {
    const tbody = document.getElementById('masterAhspTableBody');
    if (!tbody) return;

    if (isReset && newItems.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="4" class="py-5 text-center text-muted font-medium">
            ${masterSearchQuery ? `Tidak ada pekerjaan AHSP yang cocok dengan "${masterSearchQuery}".` : 'Database pekerjaan AHSP kosong.'}
          </td>
        </tr>
      `;
      return;
    }

    const startIndex = (masterPage - 1) * 50;
    newItems.forEach((item, idx) => {
      const rowNo = startIndex + idx + 1;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="text-center text-muted font-medium">${rowNo}</td>
        <td class="fw-semibold text-dark">${escapeHtml(item.nama_pekerjaan)}</td>
        <td class="text-center text-secondary">${escapeHtml(item.satuan || '')}</td>
        <td class="text-center">
          <button 
            type="button" 
            class="btn-select-outline"
            onclick='openConfirmModal(${JSON.stringify({
              code: item.id_pekerjaan,
              name: item.nama_pekerjaan,
              unit: item.satuan,
              score: 1.0
            })})'
          >
            Pilih
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  function handleSearchInputChange(val) {
    const clearBtn = document.getElementById('clearMasterSearchBtn');
    if (clearBtn) clearBtn.classList.toggle('d-none', !val.trim());
  }

  function searchMasterAhsp() {
    const input = document.getElementById('masterSearchInput');
    masterSearchQuery = input ? input.value : '';
    const resetBtn = document.getElementById('resetMasterSearchBtn');
    if (resetBtn) resetBtn.classList.toggle('d-none', !masterSearchQuery.trim());
    fetchMasterAhsp(1, true);
  }

  function clearMasterSearch() {
    const input = document.getElementById('masterSearchInput');
    if (input) input.value = '';
    masterSearchQuery = '';
    const clearBtn = document.getElementById('clearMasterSearchBtn');
    if (clearBtn) clearBtn.classList.add('d-none');
    const resetBtn = document.getElementById('resetMasterSearchBtn');
    if (resetBtn) resetBtn.classList.add('d-none');
    fetchMasterAhsp(1, true);
  }

  function loadNextMasterPage() {
    if (masterPage < masterTotalPages && !isLoadingMaster) {
      fetchMasterAhsp(masterPage + 1, false);
    }
  }

  function handleMasterScroll(e) {
    const { scrollTop, scrollHeight, clientHeight } = e.target;
    if (scrollHeight - scrollTop - clientHeight < 120) {
      if (masterPage < masterTotalPages && !isLoadingMaster) {
        fetchMasterAhsp(masterPage + 1, false);
      }
    }
  }

  // Confirmation Modal
  function openConfirmModal(candData) {
    selectedCandidate = candData;
    document.getElementById('confirmModalSelectedName').textContent = candData.name;
    document.getElementById('confirmModalSelectedUnit').textContent = candData.unit || '-';
    if (confirmModalInstance) {
      confirmModalInstance.show();
    }
  }

  // Apply Pemetaan
  async function applyConfirmedAhsp() {
    if (!selectedCandidate || !TARGET_ITEM_ID) return;

    const btn = document.getElementById('btnConfirmApply');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    try {
      const response = await fetch(`<?= base_url('api/estimation-items') ?>/${TARGET_ITEM_ID}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ahsp_code: selectedCandidate.code,
          ahsp_name: selectedCandidate.name,
          ahsp_unit: selectedCandidate.unit,
          unit: selectedCandidate.unit,
          ahsp_status: 'mapped_high',
          ahsp_score: 1.0,
          warning_note: ''
        })
      });

      const resJson = await response.json();

      if (confirmModalInstance) confirmModalInstance.hide();

      showToast('Pemetaan Berhasil!', `Item berhasil dipetakan ke "${selectedCandidate.name}" (${selectedCandidate.unit}).`, 'success');

      setTimeout(() => {
        window.location.href = RETURN_URL;
      }, 700);

    } catch (err) {
      console.error('Gagal menyimpan pemetaan AHSP:', err);
      showToast('Gagal', 'Terjadi kesalahan saat menyimpan pemetaan AHSP.', 'danger');
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Ya, Simpan Pemetaan';
      }
    }
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
</script>
<?= $this->endSection() ?>
