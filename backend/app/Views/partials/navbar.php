<?php
/**
 * Reusable Top Navbar Component - Estimator.id
 * Matched with frontend/src/components/Navbar.jsx
 */
$request = service('request');
$requestUri = $_SERVER['REQUEST_URI'] ?? (string) $request->getUri();
$path = parse_url($requestUri, PHP_URL_PATH) ?? '';

// Determine route states matching frontend logic
$isInsideProject = str_contains($path, '/anggaran') || str_contains($path, '/pemetaan-ahsp') || str_contains($path, '/rab');
$isProyekActive = $path === '/' || str_contains($path, '/proyek') || (!$isInsideProject && !str_contains($path, '/buat-proyek'));
$isHasilDeteksiActive = str_contains($path, '/anggaran') || str_contains($path, '/pemetaan-ahsp');
$isRabActive = str_contains($path, '/rab');

// Menu RAB hidden when user is still on Hasil Deteksi / Anggaran
$isAnggaranPage = str_contains($path, '/anggaran') || str_contains($path, '/pemetaan-ahsp');
$shouldShowRab = $isInsideProject && !$isAnggaranPage;

$activeProjectId = $request->getGet('id') ?: $request->getGet('uuid') ?: ($project['uuid'] ?? ($project['id'] ?? ''));
$queryString = !empty($activeProjectId) ? '?id=' . esc($activeProjectId) : '';
$anggaranUrl = '/anggaran' . $queryString;
$rabUrl = '/rab' . $queryString;
?>
<header class="app-navbar">
  <div class="navbar-container">
    <!-- Brand Logo -->
    <a href="/proyek" class="d-flex align-items-center gap-2 text-decoration-none brand-logo-group">
      <div class="brand-logo-icon">
        <svg viewBox="0 0 100 100" class="brand-svg">
          <path d="M50 15L15 45h12v40h46V45h12L50 15z" stroke="#059669" stroke-width="7" fill="none" />
          <rect x="35" y="55" width="8" height="20" rx="1.5" fill="#059669" />
          <rect x="46" y="45" width="8" height="30" rx="1.5" fill="#059669" />
          <rect x="57" y="37" width="8" height="38" rx="1.5" fill="#059669" />
          <path d="M68 25l4-4v10l-4-6z" fill="#059669" />
        </svg>
      </div>
      <div class="d-flex align-items-baseline">
        <span class="brand-logo-title">ESTIMATOR</span>
        <span class="brand-logo-subtitle">.ID</span>
      </div>
    </a>

    <!-- Navigation & Avatar -->
    <div class="d-flex align-items-center gap-4 h-100">
      <nav class="d-flex align-items-center h-100 navbar-nav-links">
        <!-- Menu Proyek: Selalu ada -->
        <div class="nav-item-wrapper h-100 d-flex align-items-center position-relative">
          <a href="/proyek" class="nav-link-proyek <?= $isProyekActive ? 'active' : '' ?>">
            Proyek
          </a>
          <?php if ($isProyekActive): ?>
            <div class="nav-active-indicator"></div>
          <?php endif; ?>
        </div>

        <!-- Menu saat proyek dibuka: Hasil Deteksi dan RAB -->
        <?php if ($isInsideProject): ?>
          <div class="nav-item-wrapper h-100 d-flex align-items-center position-relative">
            <a href="<?= $anggaranUrl ?>" class="nav-link-proyek <?= $isHasilDeteksiActive ? 'active' : '' ?>">
              Hasil Deteksi
            </a>
            <?php if ($isHasilDeteksiActive): ?>
              <div class="nav-active-indicator"></div>
            <?php endif; ?>
          </div>

          <?php if ($shouldShowRab): ?>
            <div class="nav-item-wrapper h-100 d-flex align-items-center position-relative">
              <a href="<?= $rabUrl ?>" class="nav-link-proyek <?= $isRabActive ? 'active' : '' ?>">
                RAB
              </a>
              <?php if ($isRabActive): ?>
                <div class="nav-active-indicator"></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Menu API Docs -->
        <div class="nav-item-wrapper h-100 d-flex align-items-center position-relative">
          <a href="/docs" target="_blank" class="nav-link-proyek d-flex align-items-center gap-1" title="Buka Dokumentasi API Interaktif">
            <i class="bi bi-code-slash text-success"></i>
            API Docs
          </a>
        </div>
      </nav>

      <!-- Avatar Button (Green circle with SVG profile icon) -->
      <button type="button" class="avatar-btn" title="Profil Pengguna" onclick="showToast('Profil', 'Pengguna: Administrator Estimator', 'info')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="avatar-icon">
          <path d="M18 20a6 6 0 0 0-12 0" />
          <circle cx="12" cy="10" r="4" />
          <circle cx="12" cy="12" r="10" />
        </svg>
      </button>
    </div>
  </div>
</header>

