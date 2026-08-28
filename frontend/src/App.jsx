import React from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import Project from './pages/Project'
import Anggaran from './pages/Anggaran'
import PemetaanAhsp from './pages/PemetaanAhsp'

const App = () => {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Project />} />
        <Route path="/anggaran" element={<Anggaran />} />
        <Route path="/pemetaan-ahsp" element={<PemetaanAhsp />} />
        <Route path="/estimasi" element={<Navigate to="/anggaran" replace />} />
      </Routes>
    </Router>
  )
}

export default App