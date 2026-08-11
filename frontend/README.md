# 📱 Frontend App - AI Quantity Estimator

Interactive single-page dashboard built with **React** and **Vite** for uploading DED/CAD engineering drawings, managing project details, visualizing auto-calculated Bill of Quantities (BOQ / RAB), and generating reports.

---

## 🛠️ Tech Stack & Dependencies

- **Framework**: React 18 + Vite
- **Styling**: Tailwind CSS / Vanilla CSS & Lucide Icons (`Icons.jsx`)
- **HTTP Client**: Native Fetch API / Axios (`services/api.js`)
- **State Management**: React State (`useState`, `useEffect`) & `localStorage` persistence

---

## 📂 Folder & File Structure

```text
frontend/
├── public/                 # Static public assets
├── src/
│   ├── assets/             # Images and design assets
│   ├── components/         # Reusable UI components
│   │   ├── Navbar.jsx      # Top navigation bar
│   │   └── Icons.jsx       # Custom SVG icon set
│   ├── pages/              # Application views & routes
│   │   ├── Project.jsx     # File upload & project creation page
│   │   ├── Anggaran.jsx    # Interactive RAB / WBS table manager
│   │   └── Laporan.jsx     # Cost summary and export report page
│   ├── services/
│   │   └── api.js          # API service layer & data mapping
│   ├── App.jsx             # Main app component & simple router
│   ├── main.jsx            # Application entry point
│   └── index.css           # Global CSS styles
├── .env                    # Environment variables (VITE_API_URL)
├── .env.example            # Environment template
├── vite.config.js          # Vite configuration
└── package.json            # NPM dependencies & scripts
```

---

## ⚙️ Environment Variables (`.env`)

Configure the backend proxy or API endpoint in `frontend/.env`:

```ini
# API Service URL (Points to CodeIgniter 4 Backend)
VITE_API_URL=http://localhost:8080/api/rab/analyze-image
```

---

## 🚀 Running locally

### 1. Install Dependencies
```bash
npm install
```

### 2. Start Development Server
```bash
npm run dev
```
The app will run at `http://localhost:5173`.

### 3. Build for Production
```bash
npm run build
```

---

## 📡 API Integration Layer (`src/services/api.js`)

The frontend communicates with the backend via `uploadDEDFile()` in `api.js`:
- Sends `multipart/form-data` containing `name`, `client`, and `ded_file`.
- Maps raw backend response into UI-compatible flat section/item structures (`mapToFrontendFormat`).
- Handles response caching in `localStorage` for offline access and page navigation between `Project.jsx`, `Anggaran.jsx`, and `Laporan.jsx`.
