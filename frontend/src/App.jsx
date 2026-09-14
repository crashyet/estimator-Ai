import React from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { ProjectProvider } from './context/ProjectContext'
import Project from './pages/Project'
import BuatProyek from './pages/BuatProyek'
import Anggaran from './pages/Anggaran'
import PemetaanAhsp from './pages/PemetaanAhsp'
import RabPage from './pages/RabPage'

const App = () => {
  return (
    <Router>
      <ProjectProvider>
        <Routes>
          <Route path="/" element={<Project />} />
          <Route path="/proyek" element={<Project />} />
          <Route path="/buat-proyek" element={<BuatProyek />} />
          <Route path="/buat_proyek" element={<BuatProyek />} />
          <Route path="/anggaran" element={<Anggaran />} />
          <Route path="/pemetaan-ahsp" element={<PemetaanAhsp />} />
          <Route path="/rab" element={<RabPage />} />
          <Route path="/estimasi" element={<Navigate to="/anggaran" replace />} />
        </Routes>
      </ProjectProvider>
    </Router>
  )
}

export default App