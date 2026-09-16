<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Aplikasi RAB Online - <?= esc($project['title']) ?> | Estimator.id
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* ------------------------------------------------------------- */
  /* HERO BANNER (IDENTIK 100% DENGAN ANGGARAN.PHP)                */
  /* ------------------------------------------------------------- */
  .anggaran-banner {
    position: relative;
    width: 100%;
    background-color: #79bf39;
    background: linear-gradient(135deg, #74b836 0%, #88c946 50%, #68a82d 100%);
    padding: 38px 20px 75px 20px;
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

  /* ------------------------------------------------------------- */
  /* MAIN CARD WORKSPACE (IDENTIK DENGAN ANGGARAN.PHP)             */
  /* ------------------------------------------------------------- */
  .anggaran-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
    padding: 24px 28px;
    margin: 20px auto 50px auto;
    position: relative;
    z-index: 5;
  }

  /* Search Bar */
  .rab-search-box {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .rab-search-label {
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    white-space: nowrap;
  }
  .rab-search-input {
    padding: 6px 12px;
    font-size: 12px;
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    width: 220px;
    color: #334155;
    transition: all 0.15s ease;
  }
  .rab-search-input:focus {
    outline: none;
    border-color: #087f23;
    box-shadow: 0 0 0 2px rgba(8, 127, 35, 0.12);
  }

  /* ------------------------------------------------------------- */
  /* TABLE DESIGN (1:1 DENGAN SCREENSHOT REFERENSI)               */
  /* ------------------------------------------------------------- */
  .rab-table-wrapper {
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 80vh;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
  }
  .rab-table-wrapper::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  .rab-table-wrapper::-webkit-scrollbar-track {
    background: #f8fafc;
  }
  .rab-table-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }

  .rab-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    margin-bottom: 0;
  }

  /* Sticky Thead: Solid Dark Green */
  .rab-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #087f23;
  }
  .rab-table th {
    background-color: #087f23 !important;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 14px;
    letter-spacing: 0.02em;
    border: none;
    white-space: nowrap;
    user-select: none;
  }

  .rab-table td {
    padding: 10px 14px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Category Header Row */
  .rab-sec-row {
    background-color: #ffffff;
    transition: background-color 0.15s ease;
  }
  .rab-sec-row:hover {
    background-color: #f0f7ec;
  }
  .rab-sec-title {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12.5px;
    color: #1e293b;
    letter-spacing: 0.03em;
    cursor: pointer;
    user-select: none;
  }

  /* Toggle Collapse Circle (Red) */
  .btn-toggle-sec {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background-color: #d32f2f;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    cursor: pointer;
    padding: 0;
    margin: 0 auto;
    transition: transform 0.1s ease, background-color 0.15s ease;
    box-shadow: 0 1px 2px rgba(211, 47, 47, 0.25);
  }
  .btn-toggle-sec:hover {
    background-color: #b71c1c;
  }
  .btn-toggle-sec:active {
    transform: scale(0.95);
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

  /* Category Action Buttons (+ and Trash in Green) */
  .btn-sec-action {
    width: 22px;
    height: 22px;
    border-radius: 4px;
    background-color: #689f38;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .btn-sec-action:hover {
    background-color: #558b2f;
  }

  /* Item Rows */
  .rab-row-item {
    background-color: rgba(248, 250, 252, 0.55);
    transition: background-color 0.15s ease;
  }
  .rab-row-item:hover {
    background-color: #f1f5f9;
  }
  .rab-item-name {
    font-weight: 600;
    font-size: 12px;
    color: #1e293b;
  }

  /* Badge Unmapped */
  .badge-unmapped-mini {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    background-color: #fee2e2;
    border: 1px solid #f87171;
    color: #dc2626;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 800;
    margin-left: 6px;
    vertical-align: middle;
  }

  /* Item Action Icons (Edit, Book, Trash) */
  .btn-item-icon {
    background: none;
    border: none;
    padding: 3px 5px;
    border-radius: 4px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    text-decoration: none;
  }
  .btn-item-icon.edit-icon {
    color: #64748b;
  }
  .btn-item-icon.edit-icon:hover {
    color: #1e293b;
    background-color: #e2e8f0;
  }
  .btn-item-icon.book-icon {
    color: #047857;
  }
  .btn-item-icon.book-icon:hover {
    color: #065f46;
    background-color: #ecfdf5;
  }
  .btn-item-icon.trash-icon {
    color: #ef4444;
  }
  .btn-item-icon.trash-icon:hover {
    color: #dc2626;
    background-color: #fee2e2;
  }

  /* Tabular Numerics */
  .tab-num {
    font-variant-numeric: tabular-nums;
  }

  /* Sticky Footer Solid Green */
  .rab-table tfoot {
    position: sticky;
    bottom: -1px;
    z-index: 10;
    background-color: #087f23 !important;
    color: #ffffff !important;
    user-select: none;
    box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.05);
  }
  .rab-table tfoot tr {
    background-color: #087f23 !important;
    border-top: 1px solid rgba(0, 110, 36, 0.35);
  }
  .rab-table tfoot td {
    background-color: #087f23 !important;
    color: #ffffff !important;
    font-weight: 700;
    font-size: 12px;
    padding: 10px 14px;
    border: none;
  }
  .rab-table tfoot tr.total-row td {
    font-weight: 800;
    font-size: 12.5px;
    padding: 11px 14px;
  }

  /* Print Optimization */
  @media print {
    .app-navbar, .rab-search-box, .no-print, .btn-item-icon, .btn-sec-action, .btn-toggle-sec {
      display: none !important;
    }
    .anggaran-card {
      border: none !important;
      box-shadow: none !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .rab-table-wrapper {
      max-height: none !important;
      border: 1px solid #000000 !important;
    }
    .rab-table th, .rab-table tfoot {
      background-color: #087f23 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Banner (Identik dengan Desain Anggaran) -->
<div class="anggaran-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Estimator" class="anggaran-banner-bg" onerror="this.style.display='none'">
  <h1 class="anggaran-banner-title"><?= esc($project['title']) ?></h1>
</div>

<!-- Main Workspace Container (Sama persis lebar & paddingnya dengan Anggaran: container-fluid max-width 1440px) -->
<main class="container-fluid px-3 px-md-5 my-4" style="max-width: 1440px;">
  <div class="anggaran-card">

    <!-- Top Control: Search Filter -->
    <div class="d-flex align-items-center justify-content-end mb-3 no-print">
      <div class="rab-search-box">
        <span class="rab-search-label">Cari Data:</span>
        <input 
          type="text" 
          id="rabSearchInput" 
          placeholder="Masukkan kata kunci..." 
          class="rab-search-input"
          oninput="handleRabSearch(this.value)"
        >
      </div>
    </div>

    <!-- Table Container -->
    <div class="rab-table-wrapper">
      <table class="rab-table" id="rabMainTable">
        <!-- Thead: Solid Dark Green Header -->
        <thead>
          <tr>
            <th scope="col" class="text-center" style="width: 48px;">No.</th>
            <th scope="col" class="text-start" style="min-width: 280px;">Uraian Pekerjaan</th>
            <th scope="col" class="text-center" style="width: 120px;">Kode AHSP</th>
            <th scope="col" class="text-center" style="width: 96px;">Volume</th>
            <th scope="col" class="text-center" style="width: 80px;">Satuan</th>
            <th scope="col" class="text-end" style="width: 130px;">Harga Satuan</th>
            <th scope="col" class="text-end" style="width: 130px;">Harga</th>
            <th scope="col" class="text-end" style="width: 80px;">%</th>
            <th scope="col" class="text-center" style="width: 96px;">Aksi</th>
          </tr>
        </thead>

        <!-- Tbody: Categories & Sub-items -->
        <tbody id="rabTableBody">
          <?php if (empty($sections)): ?>
            <tr>
              <td colspan="9" class="py-5 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada data pekerjaan untuk proyek ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sections as $sIdx => $sec): ?>
              <!-- Category / Section Header Row -->
              <tr class="rab-sec-row" data-sec-code="<?= esc($sec['code']) ?>">
                <!-- Col 1: Red Minus/Plus Circle Toggle -->
                <td class="text-center">
                  <button 
                    type="button" 
                    class="btn-toggle-sec" 
                    onclick="toggleCategoryCollapse('<?= esc($sec['code']) ?>')"
                    title="Buka / Tutup rincian pekerjaan kategori ini"
                  >
                    <span class="minus-line" id="minus-line-<?= esc($sec['code']) ?>"></span>
                    <span class="plus-icon d-none" id="plus-icon-<?= esc($sec['code']) ?>">+</span>
                  </button>
                </td>

                <!-- Col 2: Category Title -->
                <td class="rab-sec-title" onclick="toggleCategoryCollapse('<?= esc($sec['code']) ?>')">
                  <?= esc($sec['name']) ?>
                </td>

                <!-- Col 3: Kode AHSP (Blank) -->
                <td></td>

                <!-- Col 4: Volume (Blank) -->
                <td></td>

                <!-- Col 5: Satuan (Blank) -->
                <td></td>

                <!-- Col 6: Harga Satuan (Blank) -->
                <td></td>

                <!-- Col 7: Harga (Section Subtotal) -->
                <td class="text-end fw-medium text-dark tab-num" style="font-size: 12px;" id="sec-subtotal-<?= esc($sec['code']) ?>">
                  Rp <?= number_format($sec['subtotal'], 2, ',', '.') ?>
                </td>

                <!-- Col 8: % (Section Bobot) -->
                <td class="text-end fw-medium text-dark tab-num" style="font-size: 12px;" id="sec-bobot-<?= esc($sec['code']) ?>">
                  <?= number_format($sec['bobot'], 2, ',', '.') ?> %
                </td>

                <!-- Col 9: Aksi (Green + and Trash buttons) -->
                <td class="text-center">
                  <div class="d-inline-flex align-items-center justify-content-center gap-1.5">
                    <button 
                      type="button" 
                      class="btn-sec-action" 
                      onclick="openAddRabItemModal('<?= esc($sec['id']) ?>', '<?= esc($sec['code']) ?>')" 
                      title="Tambah Pekerjaan"
                    >
                      <i class="bi bi-plus" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn-sec-action" 
                      onclick="confirmDeleteSection('<?= esc($sec['id']) ?>', '<?= esc(addslashes($sec['name'])) ?>')" 
                      title="Hapus Kategori"
                    >
                      <i class="bi bi-trash" style="font-size: 12px; line-height: 1;"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Sub-item Rows in this Category -->
              <?php foreach ($sec['items'] as $iIdx => $it): ?>
                <tr 
                  class="rab-row-item sec-items-<?= esc($sec['code']) ?>" 
                  data-sec-code="<?= esc($sec['code']) ?>"
                  data-item-id="<?= esc($it['id']) ?>"
                  data-item-name="<?= esc(strtolower($it['name'])) ?>"
                  data-ahsp-code="<?= esc(strtolower($it['ahsp_code'] ?? '')) ?>"
                >
                  <!-- Col 1: Item No. -->
                  <td class="text-center text-muted fw-semibold tab-num" style="font-size: 11.5px;">
                    <?= esc($it['no']) ?>
                  </td>

                  <!-- Col 2: Uraian Pekerjaan & Warning Badge if Unmapped -->
                  <td style="padding-left: 32px;">
                    <div class="d-flex align-items-center gap-1.5">
                      <span class="rab-item-name">
                        <?= esc($it['ahsp_name'] ?: $it['name']) ?>
                      </span>
                      <?php if (($it['ahsp_status'] ?? '') === 'unmapped'): ?>
                        <span class="badge-unmapped-mini" title="AHSP belum dipetakan">!</span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Col 3: Kode AHSP -->
                  <td class="text-center text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= esc($it['ahsp_code'] ?: '-') ?>
                  </td>

                  <!-- Col 4: Volume -->
                  <td class="text-center text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= number_format($it['volume'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 5: Satuan -->
                  <td class="text-center text-muted" style="font-size: 12px;">
                    <?= esc($it['unit']) ?>
                  </td>

                  <!-- Col 6: Harga Satuan -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    Rp <?= number_format($it['unit_price'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 7: Harga (Total) -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    Rp <?= number_format($it['subtotal'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 8: % (Bobot) -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= number_format($it['bobot'], 2, ',', '.') ?> %
                  </td>

                  <!-- Col 9: Aksi (Pencil, Book, Trash) -->
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                      <!-- Edit Item -->
                      <button 
                        type="button" 
                        class="btn-item-icon edit-icon" 
                        onclick="openEditItemModal('<?= esc($it['id']) ?>', '<?= esc(addslashes($it['name'])) ?>', <?= (float)$it['volume'] ?>, '<?= esc($it['unit']) ?>', <?= (float)$it['unit_price'] ?>)" 
                        title="Ubah Item"
                      >
                        <i class="bi bi-pencil-square" style="font-size: 13.5px;"></i>
                      </button>

                      <!-- Pemetaan AHSP -->
                      <a 
                        href="<?= base_url('pemetaan-ahsp') ?>?id=<?= urlencode($project['uuid'] ?: $project['id']) ?>&item=<?= urlencode($it['id']) ?>" 
                        class="btn-item-icon book-icon" 
                        title="Pemetaan AHSP"
                      >
                        <i class="bi bi-book" style="font-size: 13.5px;"></i>
                      </a>

                      <!-- Hapus Item -->
                      <button 
                        type="button" 
                        class="btn-item-icon trash-icon" 
                        onclick="confirmDeleteItem('<?= esc($it['id']) ?>', '<?= esc(addslashes($it['name'])) ?>')" 
                        title="Hapus Item"
                      >
                        <i class="bi bi-trash" style="font-size: 13.5px;"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>

        <!-- Tfoot: Solid Dark Green Summary (Matching Screenshot) -->
        <tfoot>
          <!-- Row 1: JUMLAH HARGA -->
          <tr>
            <td colspan="6" class="text-end text-uppercase fw-bold text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              JUMLAH HARGA
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" id="tfootJumlahHarga" style="background-color: #087f23 !important;">
              Rp <?= number_format($grandTotal, 2, ',', '.') ?>
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" style="background-color: #087f23 !important;">
              <?= $grandTotal > 0 ? '100.00 %' : '0.00 %' ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>

          <!-- Row 2: PPN 0.00 % -->
          <tr>
            <td colspan="6" class="text-end text-uppercase fw-bold text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              PPN <?= number_format($ppnRate, 2, '.', '') ?> %
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" id="tfootPpnAmount" style="background-color: #087f23 !important;">
              Rp <?= number_format($grandTotal * ($ppnRate / 100), 2, ',', '.') ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>

          <!-- Row 3: TOTAL HARGA -->
          <tr class="total-row">
            <td colspan="6" class="text-end text-uppercase fw-bolder text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              TOTAL HARGA
            </td>
            <td class="text-end fw-bolder text-white tab-num pe-3" id="tfootTotalHarga" style="background-color: #087f23 !important;">
              Rp <?= number_format($grandTotal * (1 + ($ppnRate / 100)), 2, ',', '.') ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>
</main>

<!-- Modal: Ubah Item (Volume & Harga Satuan) -->
<div class="modal fade" id="editRabItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-dark text-white py-2.5 px-3">
        <h6 class="modal-title fs-6 fw-bold">
          <i class="bi bi-pencil-square me-1"></i> Ubah Item Pekerjaan
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="editRabItemId">
        <div class="mb-2">
          <label class="form-label text-muted small mb-1 fw-medium" id="editRabItemName">-</label>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold mb-1">Volume</label>
          <div class="input-group input-group-sm">
            <input type="number" step="0.01" class="form-control" id="editRabVolumeInput" min="0">
            <span class="input-group-text" id="editRabUnitSpan">m2</span>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Harga Satuan (Rp)</label>
          <input type="number" step="500" class="form-control form-control-sm" id="editRabPriceInput" min="0">
        </div>
      </div>
      <div class="modal-footer bg-light p-2 justify-content-end">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="saveRabItemEdit()" style="background-color: #00802b; border-color: #00802b;">
          Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Tambah Pekerjaan Baru -->
<div class="modal fade" id="addRabItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-success text-white py-2.5 px-3" style="background-color: #087f23 !important;">
        <h6 class="modal-title fs-6 fw-bold">
          <i class="bi bi-plus-circle me-1"></i> Tambah Pekerjaan
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="addRabSectionId">
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Uraian Pekerjaan</label>
          <input type="text" class="form-control form-control-sm" id="addRabItemNameInput" placeholder="Nama pekerjaan...">
        </div>
        <div class="row g-2 mb-2">
          <div class="col-7">
            <label class="form-label small fw-semibold mb-1">Volume</label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="addRabItemVolInput" value="1.00">
          </div>
          <div class="col-5">
            <label class="form-label small fw-semibold mb-1">Satuan</label>
            <input type="text" class="form-control form-control-sm" id="addRabItemUnitInput" value="m2">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Harga Satuan (Rp)</label>
          <input type="number" step="500" class="form-control form-control-sm" id="addRabItemPriceInput" value="0">
        </div>
      </div>
      <div class="modal-footer bg-light p-2 justify-content-end">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="executeAddRabItem()" style="background-color: #087f23; border-color: #087f23;">
          Tambah
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Konfirmasi Hapus -->
<div class="modal fade" id="deleteRabModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-body p-4 text-center">
        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
          <i class="bi bi-trash fs-4"></i>
        </div>
        <h6 class="fw-bold mb-2" id="deleteRabModalTitle">Hapus Pekerjaan Ini?</h6>
        <p class="text-secondary small mb-4" id="deleteRabModalDesc">Apakah Anda yakin ingin menghapus pekerjaan ini dari tabel RAB?</p>
        <input type="hidden" id="deleteRabTargetId">
        <input type="hidden" id="deleteRabTargetType">
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-light btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger btn-sm px-3 rounded-pill" onclick="executeDeleteRab()">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  let editModalInstance = null;
  let addModalInstance = null;
  let deleteModalInstance = null;
  const collapsedSections = {};

  document.addEventListener('DOMContentLoaded', () => {
    editModalInstance = new bootstrap.Modal(document.getElementById('editRabItemModal'));
    addModalInstance = new bootstrap.Modal(document.getElementById('addRabItemModal'));
    deleteModalInstance = new bootstrap.Modal(document.getElementById('deleteRabModal'));
  });

  // Toggle Category Collapse/Expand
  function toggleCategoryCollapse(secCode) {
    const isCurrentlyCollapsed = collapsedSections[secCode] === true;
    collapsedSections[secCode] = !isCurrentlyCollapsed;

    const minusLine = document.getElementById(`minus-line-${secCode}`);
    const plusIcon = document.getElementById(`plus-icon-${secCode}`);
    const subRows = document.querySelectorAll(`.sec-items-${secCode}`);

    if (collapsedSections[secCode]) {
      if (minusLine) minusLine.classList.add('d-none');
      if (plusIcon) plusIcon.classList.remove('d-none');
      subRows.forEach(r => r.classList.add('d-none'));
    } else {
      if (minusLine) minusLine.classList.remove('d-none');
      if (plusIcon) plusIcon.classList.add('d-none');
      subRows.forEach(r => r.classList.remove('d-none'));
    }
  }

  // Live Search Filter
  function handleRabSearch(val) {
    const term = (val || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.rab-row-item');
    const secRows = document.querySelectorAll('.rab-sec-row');

    if (!term) {
      rows.forEach(r => {
        const code = r.dataset.secCode;
        if (!collapsedSections[code]) {
          r.classList.remove('d-none');
        }
      });
      secRows.forEach(s => s.classList.remove('d-none'));
      return;
    }

    const matchingSecs = new Set();

    rows.forEach(r => {
      const name = r.dataset.itemName || '';
      const ahspCode = r.dataset.ahspCode || '';
      const secCode = r.dataset.secCode || '';

      if (name.includes(term) || ahspCode.includes(term)) {
        r.classList.remove('d-none');
        matchingSecs.add(secCode);
      } else {
        r.classList.add('d-none');
      }
    });

    secRows.forEach(s => {
      const code = s.dataset.secCode;
      if (matchingSecs.has(code) || (s.innerText || '').toLowerCase().includes(term)) {
        s.classList.remove('d-none');
      } else {
        s.classList.add('d-none');
      }
    });
  }

  // Open Edit Item Modal
  function openEditItemModal(id, name, volume, unit, price) {
    document.getElementById('editRabItemId').value = id;
    document.getElementById('editRabItemName').textContent = name;
    document.getElementById('editRabVolumeInput').value = volume;
    document.getElementById('editRabUnitSpan').textContent = unit;
    document.getElementById('editRabPriceInput').value = price;
    editModalInstance.show();
  }

  async function saveRabItemEdit() {
    const id = document.getElementById('editRabItemId').value;
    const vol = parseFloat(document.getElementById('editRabVolumeInput').value) || 0;
    const price = parseFloat(document.getElementById('editRabPriceInput').value) || 0;

    try {
      const res = await fetch(`<?= base_url('api/estimation-items') ?>/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ volume: vol, unit_price: price })
      });

      if (res.ok) {
        showToast('Sukses', 'Data pekerjaan berhasil diperbarui.', 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast('Gagal', 'Gagal memperbarui pekerjaan.', 'error');
      }
    } catch (e) {
      showToast('Error', e.message || 'Terjadi kesalahan.', 'error');
    } finally {
      editModalInstance.hide();
    }
  }

  // Open Add Item Modal
  function openAddRabItemModal(secId, secCode) {
    document.getElementById('addRabSectionId').value = secId;
    document.getElementById('addRabItemNameInput').value = '';
    document.getElementById('addRabItemVolInput').value = '1.00';
    document.getElementById('addRabItemPriceInput').value = '0';
    addModalInstance.show();
  }

  async function executeAddRabItem() {
    const secId = document.getElementById('addRabSectionId').value;
    const name = document.getElementById('addRabItemNameInput').value.trim();
    const vol = parseFloat(document.getElementById('addRabItemVolInput').value) || 1;
    const unit = document.getElementById('addRabItemUnitInput').value.trim() || 'm2';
    const price = parseFloat(document.getElementById('addRabItemPriceInput').value) || 0;

    if (!name) {
      showToast('Peringatan', 'Uraian pekerjaan tidak boleh kosong.', 'warning');
      return;
    }

    try {
      const res = await fetch(`<?= base_url('api/estimation-items') ?>`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          section_id: secId,
          item_name: name,
          volume: vol,
          unit: unit,
          unit_price: price,
          ahsp_status: 'unmapped'
        })
      });

      if (res.ok) {
        showToast('Sukses', 'Pekerjaan baru berhasil ditambahkan.', 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast('Gagal', 'Gagal menambahkan pekerjaan.', 'error');
      }
    } catch (e) {
      showToast('Error', e.message || 'Terjadi kesalahan.', 'error');
    } finally {
      addModalInstance.hide();
    }
  }

  // Confirm Delete Item / Section
  function confirmDeleteItem(id, name) {
    document.getElementById('deleteRabTargetId').value = id;
    document.getElementById('deleteRabTargetType').value = 'item';
    document.getElementById('deleteRabModalTitle').textContent = 'Hapus Pekerjaan Ini?';
    document.getElementById('deleteRabModalDesc').textContent = `Apakah Anda yakin ingin menghapus "${name}" dari tabel RAB?`;
    deleteModalInstance.show();
  }

  function confirmDeleteSection(id, name) {
    document.getElementById('deleteRabTargetId').value = id;
    document.getElementById('deleteRabTargetType').value = 'section';
    document.getElementById('deleteRabModalTitle').textContent = 'Hapus Kategori Ini?';
    document.getElementById('deleteRabModalDesc').textContent = `Apakah Anda yakin ingin menghapus seluruh kategori "${name}" beserta pekerjaan di dalamnya?`;
    deleteModalInstance.show();
  }

  async function executeDeleteRab() {
    const id = document.getElementById('deleteRabTargetId').value;
    const type = document.getElementById('deleteRabTargetType').value;

    try {
      const endpoint = type === 'item' 
        ? `<?= base_url('api/estimation-items') ?>/${id}`
        : `<?= base_url('api/wbs-sections') ?>/${id}`;

      const res = await fetch(endpoint, { method: 'DELETE' });

      if (res.ok) {
        showToast('Terhapus', 'Data berhasil dihapus dari RAB.', 'warning');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast('Gagal', 'Gagal menghapus data.', 'error');
      }
    } catch (e) {
      showToast('Error', e.message || 'Terjadi kesalahan.', 'error');
    } finally {
      deleteModalInstance.hide();
    }
  }
</script>
<?= $this->endSection() ?>
