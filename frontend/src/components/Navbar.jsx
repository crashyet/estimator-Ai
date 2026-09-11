import React from 'react'
import { Link, useLocation } from 'react-router-dom'
import { Logo, BusinessAvatar, Icons } from './Icons'

const Navbar = ({ onNavigateToRab, hideRab }) => {
  const location = useLocation()
  
  const isInsideProject = location.pathname.startsWith('/anggaran') || location.pathname.startsWith('/pemetaan-ahsp') || location.pathname.startsWith('/rab')
  const isProyekActive = location.pathname === '/' || location.pathname.startsWith('/proyek')
  const isHasilDeteksiActive = location.pathname.startsWith('/anggaran') || location.pathname.startsWith('/pemetaan-ahsp')
  const isRabActive = location.pathname.startsWith('/rab')

  // Menu RAB di-hidden saat pengguna masih di halaman Hasil Deteksi / Anggaran
  const isAnggaranPage = location.pathname.startsWith('/anggaran') || location.pathname.startsWith('/pemetaan-ahsp')
  const shouldShowRab = !hideRab && isInsideProject && !isAnggaranPage

  return (
    <header className="sticky top-0 z-50 bg-white border-b border-slate-100 shadow-xs backdrop-blur-md bg-white/95">
      <div className="max-w-[1360px] mx-auto px-6 h-13 flex items-center justify-between">
        <Link to="/">
          <Logo />
        </Link>
        
        <div className="flex items-center gap-6 h-full">
          <nav className="flex items-center gap-8 h-full">
            {/* Menu Proyek: Selalu ada */}
            <div className="relative h-full flex items-center">
              <Link 
                to="/proyek" 
                className={`text-[14px] font-semibold transition-colors ${
                  isProyekActive ? 'text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                Proyek
              </Link>
              {isProyekActive && (
                <div className="absolute bottom-0 left-0 right-0 h-0.75 bg-emerald-600 rounded-t-full"></div>
              )}
            </div>

            {/* Menu saat proyek dibuka: Hasil Deteksi dan RAB */}
            {isInsideProject && (
              <>
                <div className="relative h-full flex items-center">
                  <Link 
                    to={`/anggaran${location.search}`}
                    className={`text-[14px] font-semibold transition-colors ${
                      isHasilDeteksiActive ? 'text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900'
                    }`}
                  >
                    Hasil Deteksi
                  </Link>
                  {isHasilDeteksiActive && (
                    <div className="absolute bottom-0 left-0 right-0 h-0.75 bg-emerald-600 rounded-t-full"></div>
                  )}
                </div>

                {shouldShowRab && (
                  <div className="relative h-full flex items-center">
                    {onNavigateToRab ? (
                      <button
                        type="button"
                        onClick={onNavigateToRab}
                        className={`text-[14px] font-semibold transition-colors cursor-pointer ${
                          isRabActive ? 'text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        RAB
                      </button>
                    ) : (
                      <Link 
                        to={`/rab${location.search}`}
                        className={`text-[14px] font-semibold transition-colors ${
                          isRabActive ? 'text-emerald-700 font-bold' : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        RAB
                      </Link>
                    )}
                    {isRabActive && (
                      <div className="absolute bottom-0 left-0 right-0 h-0.75 bg-emerald-600 rounded-t-full"></div>
                    )}
                  </div>
                )}
              </>
            )}
          </nav>

          {/* Avatar button with green circle background */}
          <div className="w-8.5 h-8.5 rounded-full bg-[#7cb342] flex items-center justify-center text-white shadow-xs cursor-pointer hover:opacity-90 transition-opacity">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4.5 h-4.5">
              <path d="M18 20a6 6 0 0 0-12 0" />
              <circle cx="12" cy="10" r="4" />
              <circle cx="12" cy="12" r="10" />
            </svg>
          </div>
        </div>
      </div>
    </header>
  )
}

export default Navbar