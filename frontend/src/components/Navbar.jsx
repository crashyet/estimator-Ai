import React from 'react'
import { Link, useLocation } from 'react-router-dom'
import { Logo, BusinessAvatar, Icons } from './Icons'

const Navbar = ({ onResetData }) => {
  const location = useLocation()
  
  const isProyekActive = location.pathname === '/' || location.pathname.startsWith('/proyek')

  return (
    <header className="sticky top-0 z-40 bg-white border-b border-slate-100 shadow-sm backdrop-blur-md bg-white/95">
      <div className="max-w-[1240px] mx-auto px-4 h-16 flex items-center justify-between">
        <Link to="/">
          <Logo />
        </Link>
        
        <nav className="flex items-center gap-8 h-full">
          <div className="relative h-full flex items-center">
            <Link 
              to="/" 
              className={`text-[14.5px] font-semibold transition-colors ${
                isProyekActive ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              Proyek
            </Link>
            {isProyekActive && (
              <div className="absolute bottom-0 left-0 right-0 h-1 bg-emerald-600 rounded-t-full"></div>
            )}
          </div>
        </nav>

        <div className="flex items-center gap-3">
          {onResetData && (
            <button 
              onClick={onResetData}
              className="p-1.5 rounded-full text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-all mr-1 cursor-pointer"
              title="Atur Ulang Data ke Default"
            >
              <Icons.Refresh className="w-5 h-5" />
            </button>
          )}
          <BusinessAvatar />
        </div>
      </div>
    </header>
  )
}

export default Navbar