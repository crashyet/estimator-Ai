<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>API Documentation - Estimator AI</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><path d='M50 15L15 45h12v40h46V45h12L50 15z' fill='%23059669'/></svg>">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background-color: #0f172a;
    }
    .custom-nav-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 8px 24px;
      background: #090d16;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      color: #fff;
      font-size: 13px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .brand-link {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #fff;
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.5px;
    }
    .brand-link span {
      color: #10b981;
    }
    .nav-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .nav-btn {
      color: #94a3b8;
      text-decoration: none;
      padding: 5px 12px;
      border-radius: 6px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.2s ease;
      font-size: 12px;
      font-weight: 500;
    }
    .nav-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(255, 255, 255, 0.2);
    }
    .nav-btn.primary {
      background: #059669;
      color: #fff;
      border-color: #059669;
    }
    .nav-btn.primary:hover {
      background: #047857;
    }
  </style>
</head>
<body>
  <!-- Top Navigation Header -->
  <div class="custom-nav-bar">
    <a href="/" class="brand-link">
      <svg width="22" height="22" viewBox="0 0 100 100" fill="none">
        <path d="M50 15L15 45h12v40h46V45h12L50 15z" stroke="#10b981" stroke-width="7" fill="none" />
        <rect x="35" y="55" width="8" height="20" rx="1.5" fill="#10b981" />
        <rect x="46" y="45" width="8" height="30" rx="1.5" fill="#10b981" />
        <rect x="57" y="37" width="8" height="38" rx="1.5" fill="#10b981" />
      </svg>
      ESTIMATOR<span>.AI</span> &bull; API Docs
    </a>

    <div class="nav-actions">
      <a href="/docs/swagger" class="nav-btn">Switch to Swagger UI</a>
      <a href="/api/openapi.json" target="_blank" class="nav-btn">Raw OpenAPI JSON</a>
      <a href="/proyek" class="nav-btn primary">&larr; Kembali ke Aplikasi</a>
    </div>
  </div>

  <!-- Scalar API Reference Container -->
  <div id="api-reference"></div>

  <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
  <script>
    Scalar.createApiReference('#api-reference', {
      url: '/api/openapi.json',
      theme: 'purple',
      darkMode: true,
      showSidebar: true,
      layout: 'modern',
      searchHotKey: 'k',
      metaData: {
        title: 'Estimator AI - API Docs'
      }
    });
  </script>
</body>
</html>
