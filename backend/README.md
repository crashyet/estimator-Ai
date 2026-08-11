# 🐘 Backend API - CodeIgniter 4 Service

PHP web backend built on **CodeIgniter 4** framework. It acts as an API gateway and proxy layer between the React frontend and the Python AI Estimator microservice.

---

## 🛠️ Architecture & Role

The backend performs the following functions:
1. **API Gateway & Routing**: Exposes HTTP endpoints (`/api/rab/analyze` & `/api/rab/analyze-image`) consumed by the React UI.
2. **File & Request Validation**: Validates file types (`.dwg`, `.dxf`, `.pdf`), file sizes, and form parameter payload contracts.
3. **Microservice Proxying**: Wraps client uploads into cURL multipart requests and forwards them to the Python FastAPI AI service defined by `PYTHON_API_URL`.
4. **CORS & Response Headers**: Manages CORS headers and formats response payloads gracefully.

---

## 📂 Folder Structure

```text
backend/
├── app/
│   ├── Config/             # CodeIgniter 4 configuration
│   │   └── Routes.php      # API Route definitions
│   ├── Controllers/        # HTTP Controllers
│   │   ├── BaseController.php
│   │   ├── Home.php
│   │   └── RABController.php # Core proxy controller for AI estimation
│   └── Views/              # Standard CI4 views (if applicable)
├── public/                 # Web root directory (index.php)
├── writable/               # Log, cache, and temporary upload storage
├── .env                    # Active environment variables
├── env                     # Template environment file
└── spark                   # CodeIgniter 4 CLI tool
```

---

## ⚙️ Environment Variables (`.env`)

Copy `env` to `.env` in the `backend/` root directory:

```ini
# Environment
CI_ENVIRONMENT = development

# Base Application URL
app.baseURL = 'http://localhost:8080/'

# Python FastAPI Microservice Endpoint
PYTHON_API_URL = http://localhost:8200
```

---

## 🚀 Running Locally

### 1. Requirements
- PHP 8.2 or higher
- PHP Extensions: `curl`, `json`, `mbstring`, `intl`

### 2. Start Built-in Development Server
```bash
php spark serve --port 8080
```
Server runs at `http://localhost:8080`.

---

## 📡 Endpoints

### 1. `POST /api/rab/analyze-image`
- **Controller Method**: `RABController::analyzeImage`
- **Description**: Receives project details (`name`, `client`) and file (`ded_file`), forwards to `PYTHON_API_URL + /api/rab/analyze-image`, and returns the extracted JSON RAB.
