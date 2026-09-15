<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $this->renderSection('title') ?: 'Estimator.id' ?></title>
  
  <!-- Google Fonts: Quicksand -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root {
      --brand-green: #0fa83c;
      --brand-green-dark: #089613;
      --brand-green-hover: #067a0f;
      --brand-red: #eb3324;
      --brand-red-hover: #d32f2f;
      --brand-banner-bg: #84c225;
      --text-gray: #555555;
      --border-dash: #cbd5e1;
    }

    body, button, input, select, textarea, optgroup {
      font-family: 'Quicksand', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    body {
      background-color: #f7faf8;
      color: #334155;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Top Navbar Styles */
    .app-navbar {
      background-color: #ffffff;
      border-bottom: 1px solid #eef2f6;
      height: 62px;
      position: sticky;
      top: 0;
      z-index: 1030;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .brand-logo-icon {
      width: 32px;
      height: 32px;
      border-radius: 6px;
      background-color: #ecfdf5;
      border: 1px solid #a7f3d0;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .nav-link-proyek {
      color: #64748b;
      font-weight: 600;
      font-size: 14px;
      position: relative;
      padding: 19px 0;
      text-decoration: none;
      transition: color 0.15s ease;
    }
    .nav-link-proyek:hover {
      color: #047857;
    }
    .nav-link-proyek.active {
      color: #047857;
      font-weight: 700;
    }
    .nav-link-proyek.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 3px;
      background-color: #059669;
      border-top-left-radius: 3px;
      border-top-right-radius: 3px;
    }

    /* Custom Toast Notification Styling */
    .toast-container {
      top: 75px !important;
      right: 20px !important;
      z-index: 9999 !important;
    }
    .custom-toast {
      background-color: #ffffff;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15), 0 2px 6px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.08);
      overflow: hidden;
      min-width: 320px;
    }
  </style>

  <?= $this->renderSection('styles') ?>
</head>
<body>

  <!-- Reusable Navbar Partial -->
  <?= $this->include('partials/navbar') ?>

  <!-- Main Content Page Body -->
  <?= $this->renderSection('content') ?>

  <!-- Reusable Toast Container -->
  <div class="toast-container position-fixed p-3"></div>

  <!-- Bootstrap 5.3 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

  <!-- Global Toast Helper -->
  <script>
    function showToast(title, message, type = 'success') {
      const container = document.querySelector('.toast-container');
      if (!container) return;

      let iconHtml = '<i class="bi bi-info-circle-fill text-primary"></i>';
      let borderClass = 'border-primary';

      if (type === 'success') {
        iconHtml = '<i class="bi bi-check-circle-fill text-success"></i>';
        borderClass = 'border-success';
      } else if (type === 'danger') {
        iconHtml = '<i class="bi bi-exclamation-octagon-fill text-danger"></i>';
        borderClass = 'border-danger';
      } else if (type === 'warning') {
        iconHtml = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
        borderClass = 'border-warning';
      }

      const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
      const toastHtml = `
        <div id="${toastId}" class="toast custom-toast border-start border-4 ${borderClass} mb-2 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="toast-header bg-white py-2">
            <span class="toast-icon me-1">${iconHtml}</span>
            <strong class="me-auto text-dark" style="font-size: 13px;">${title}</strong>
            <small class="text-muted" style="font-size: 11px;">baru saja</small>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
          <div class="toast-body bg-white py-2 text-secondary" style="font-size: 12.5px;">
            ${message}
          </div>
        </div>
      `;

      container.insertAdjacentHTML('beforeend', toastHtml);
      const toastEl = document.getElementById(toastId);
      const bsToast = new bootstrap.Toast(toastEl, { delay: 4000, autohide: true });
      toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
      });
      bsToast.show();
    }
  </script>

  <?= $this->renderSection('scripts') ?>
</body>
</html>
