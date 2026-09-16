<?php
/**
 * Reusable Top Navbar Component - Estimator.id
 */
$request = service('request');
$requestUri = $_SERVER['REQUEST_URI'] ?? (string) $request->getUri();

$isRabActive = str_contains($requestUri, 'rab');
$isHasilDeteksiActive = (str_contains($requestUri, 'anggaran') || str_contains($requestUri, 'deteksi') || str_contains($requestUri, 'pemetaan-ahsp')) && !$isRabActive;
$isProyekActive = !$isHasilDeteksiActive && !$isRabActive;

$activeProjectId = $request->getGet('id') ?: $request->getGet('uuid') ?: ($project['uuid'] ?? ($project['id'] ?? ''));
$anggaranUrl = base_url('anggaran' . (!empty($activeProjectId) ? '?id=' . esc($activeProjectId) : ''));
$rabUrl = base_url('rab' . (!empty($activeProjectId) ? '?id=' . esc($activeProjectId) : ''));
?>
<header class="app-navbar">
  <div class="container-xl h-100 d-flex align-items-center justify-content-between px-3 px-md-4">
    <!-- Brand Logo -->
    <a href="<?= base_url() ?>" class="d-flex align-items-center gap-2 text-decoration-none">
      <div class="brand-logo-icon">
        <svg viewBox="0 0 100 100" style="width: 22px; height: 22px;" class="text-success fill-current">
          <path d="M50 15L15 45h12v40h46V45h12L50 15z" stroke="#059669" stroke-width="7" fill="none" />
          <rect x="35" y="55" width="8" height="20" rx="1.5" fill="#059669" />
          <rect x="46" y="45" width="8" height="30" rx="1.5" fill="#059669" />
          <rect x="57" y="37" width="8" height="38" rx="1.5" fill="#059669" />
          <path d="M68 25l4-4v10l-4-6z" fill="#059669" />
        </svg>
      </div>
      <div class="d-flex align-items-baseline">
        <span class="fw-bolder text-dark" style="font-size: 18px; letter-spacing: -0.02em;">ESTIMATOR</span>
        <span class="fw-bold text-success" style="font-size: 18px;">.ID</span>
      </div>
    </a>

    <!-- Navigation & Avatar -->
    <div class="d-flex align-items-center gap-4 h-100">
      <a href="<?= base_url('proyek') ?>" class="nav-link-proyek <?= $isProyekActive ? 'active' : '' ?>">
        Proyek
      </a>
      <a href="<?= $anggaranUrl ?>" class="nav-link-proyek <?= $isHasilDeteksiActive ? 'active' : '' ?>">
        Hasil Deteksi
      </a>
      <a href="<?= $rabUrl ?>" class="nav-link-proyek <?= $isRabActive ? 'active' : '' ?>">
        RAB
      </a>
      
      <!-- User Avatar Circle -->
      <button class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Profil Pengguna" onclick="showToast('Profil', 'Pengguna: Administrator Estimator', 'info')">
        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px; background-color: #0fa83c !important;">
          <i class="bi bi-person-fill fs-6"></i>
        </div>
      </button>
    </div>
  </div>
</header>
