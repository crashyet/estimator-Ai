<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Swagger UI - Estimator AI API</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.11.0/swagger-ui.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><path d='M50 15L15 45h12v40h46V45h12L50 15z' fill='%23059669'/></svg>">
  <style>
    body {
      margin: 0;
      padding: 0;
      background: #fafafa;
      font-family: sans-serif;
    }
    .custom-nav-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 24px;
      background: #1b1b1b;
      color: #fff;
      font-size: 13px;
    }
    .brand-link {
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: #fff;
      font-weight: 700;
      font-size: 15px;
    }
    .brand-link span {
      color: #10b981;
    }
    .nav-actions {
      display: flex;
      gap: 10px;
    }
    .nav-btn {
      color: #ccc;
      text-decoration: none;
      padding: 5px 12px;
      border-radius: 4px;
      background: #333;
      font-size: 12px;
    }
    .nav-btn:hover {
      color: #fff;
      background: #444;
    }
    .nav-btn.primary {
      background: #059669;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="custom-nav-bar">
    <a href="/" class="brand-link">
      ESTIMATOR<span>.AI</span> &bull; Swagger UI
    </a>
    <div class="nav-actions">
      <a href="/docs" class="nav-btn">Switch to Scalar UI (Modern)</a>
      <a href="/api/openapi.json" target="_blank" class="nav-btn">OpenAPI JSON</a>
      <a href="/proyek" class="nav-btn primary">&larr; Kembali ke Web</a>
    </div>
  </div>

  <div id="swagger-ui"></div>

  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
  <script>
    window.onload = function() {
      SwaggerUIBundle({
        url: "/api/openapi.json",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        layout: "BaseLayout"
      });
    };
  </script>
</body>
</html>
