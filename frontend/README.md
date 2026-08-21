# 📱 Frontend App - AI Quantity Estimator & AHSP Mapper

Interactive single-page dashboard built with **React** and **Vite** for uploading CAD engineering drawings, BIM models, managing project details, visualizing auto-calculated Bill of Quantities (BOQ / RAB), performing AHSP standard mapping, and generating cost reports.

---

## 🛠️ Tech Stack & Dependencies

- **Framework**: React 18 + Vite
- **Styling**: Tailwind CSS / Vanilla CSS & Lucide Custom Icons (`Icons.jsx`)
- **HTTP Client**: Native Fetch API (`services/api.js`)
- **State Management**: React State (`useState`, `useEffect`) & `localStorage` persistence

---

## 🚀 Key Features

1. **Multi-Format CAD / BIM Upload Dropzone (`Project.jsx`)**:
   - Supports 18+ engineering file formats:
     - **Vector CAD**: `.dwg`, `.dxf`, `.dwt`, `.dwf`, `.dwfx`, `.svg`, `.plt`, `.hpgl`, `.hpg`
     - **3D BIM / Cloud**: `.ifc`, `.rvt`, `.rfa`, `.nwd`, `.nwc`, `.skp`
     - **Multimodal**: `.pdf`, `.jpeg`, `.png`, `.jpg`

2. **AHSP Mapping Precision Badges & Status Indicators (`Anggaran.jsx`)**:
   - Displays real-time confidence badges for each WBS work item:
     - 🟢 **Mapped High (>= 85%)**: High-confidence automated mapping to PUPR standard.
     - 🟡 **Mapped Medium (65% – 84%)**: Medium confidence with top-3 candidate recommendations.
     - ⚪ **Unmapped (< 65%)**: Flagged for manual verification.

3. **Interactive AHSP Manual Override Modal (`Anggaran.jsx`)**:
   - Allows Quantity Surveyors (QS) to search the master dataset of 8,900+ Indonesian SE PUPR 2025 work items.
   - Live semantic search, candidate selection, and instant mapping override with visual feedback.

4. **Interactive RAB Budget & Report Export (`Laporan.jsx`)**:
   - Real-time cost recalculation, section grouping, and Excel/JSON report generation.

---

## 📂 Folder & File Structure

```text
frontend/
├── public/                 # Static public assets
├── src/
│   ├── assets/             # Images and design assets
│   ├── components/         # Reusable UI components
│   │   ├── Navbar.jsx      # Top navigation bar
│   │   └── Icons.jsx       # Custom SVG icon set (AHSP badges, upload icons)
│   ├── pages/              # Application views & routes
│   │   ├── Project.jsx     # Multi-format file upload & project creation page
│   │   ├── Anggaran.jsx    # Interactive RAB / WBS table & AHSP mapper manager
│   │   └── Laporan.jsx     # Cost summary and export report page
│   ├── services/
│   │   └── api.js          # API service layer & AHSP mapping endpoints integration
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
# API Service URL (Points to CodeIgniter 4 Backend or Python FastAPI direct)
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

- **`uploadDEDFile()`**: Uploads engineering file streams to backend (`/api/rab/analyze-image`).
- **`searchAHSP()`**: Performs real-time semantic query searches on AHSP VectorDB (`/api/ahsp/search`).
- **`mapSingleItem()`**: Maps individual work item name to AHSP dataset (`/api/ahsp/map-item`).
- **`overrideAHSPMapping()`**: Submits manual QS override assignments (`/api/ahsp/override`).
