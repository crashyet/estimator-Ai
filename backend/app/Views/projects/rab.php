<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Aplikasi RAB Online - <?= esc($project['title']) ?> | Estimator.id
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* ------------------------------------------------------------- */
  /* HERO BANNER (IDENTIK 100% DENGAN ANGGARAN.PHP)                */
  /* ------------------------------------------------------------- */
  .anggaran-banner {
    background-color: var(--brand-banner-bg);
    position: relative;
    height: 112px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: inset 0 -2px 6px rgba(0, 0, 0, 0.05);
  }
  .anggaran-banner-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-position: center right;
    pointer-events: none;
    z-index: 1;
  }
  .anggaran-banner-title {
    position: relative;
    z-index: 2;
    color: #ffffff;
    font-size: 30px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
  }

  /* ------------------------------------------------------------- */
  /* MAIN CARD WORKSPACE (IDENTIK DENGAN ANGGARAN.PHP)             */
  /* ------------------------------------------------------------- */
  .anggaran-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
    padding: 24px 28px;
    margin: 0px auto 50px auto;
    position: relative;
    z-index: 5;
  }

  /* Search Bar */
  .rab-search-box {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .rab-search-label {
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    white-space: nowrap;
  }
  .rab-search-input {
    padding: 6px 12px;
    font-size: 12px;
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    width: 220px;
    color: #334155;
    transition: all 0.15s ease;
  }
  .rab-search-input:focus {
    outline: none;
    border-color: #087f23;
    box-shadow: 0 0 0 2px rgba(8, 127, 35, 0.12);
  }

  /* Green Pill Action Toolbar */
  .rab-toolbar-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
  }
  .btn-pill-group {
    display: inline-flex;
    align-items: center;
    background-color: #8ce628;
    padding: 2px;
    border-radius: 9999px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  }
  .btn-pill-action {
    background-color: #8ce628;
    color: #1e293b;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 8px 16px;
    border: none;
    border-right: 1px solid #78cd20;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    text-decoration: none;
  }
  .btn-pill-group .btn-pill-action:first-child {
    border-top-left-radius: 9999px;
    border-bottom-left-radius: 9999px;
  }
  .btn-pill-group .btn-pill-action:last-child {
    border-top-right-radius: 9999px;
    border-bottom-right-radius: 9999px;
    border-right: none;
  }
  .btn-pill-action:hover {
    background-color: #78cd20;
    color: #0f172a;
  }

  /* Floating Fixed Side Panel Overlay (Overlaps Navbar, 440px Width) */
  .ai-side-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 440px;
    max-width: 95vw;
    z-index: 1060;
    background-color: #ffffff;
    border-left: 1px solid #e2e8f0;
    box-shadow: -10px 0 35px rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .ai-side-panel.d-none {
    display: none !important;
  }
  .ai-panel-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1055;
    background-color: transparent;
    pointer-events: none;
  }
  .ai-panel-backdrop.d-none {
    display: none !important;
  }
  .ai-side-panel .panel-header {
    background-color: #ffffff;
    color: #1e293b;
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
  }
  .ai-nav-tabs {
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    padding: 0 16px;
    flex-shrink: 0;
  }
  .ai-nav-tabs .nav-link {
    border: none;
    color: #64748b;
    font-weight: 600;
    font-size: 13px;
    padding: 12px 16px;
    border-bottom: 2px solid transparent;
    transition: all 0.15s ease;
  }
  .ai-nav-tabs .nav-link.active {
    color: #087f23;
    border-bottom-color: #087f23;
    background: #ffffff;
    font-weight: 700;
  }

  /* Audit Tab Cards & Dynamic States */
  .audit-circle-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }
  /* Interactive SVG Donut Slice Hover */
  circle.donut-slice-item {
    cursor: pointer;
    transition: stroke-width 0.2s ease, filter 0.2s ease;
  }
  circle.donut-slice-item:hover {
    stroke-width: 15px !important;
    filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.25));
  }
  /* Dynamic Mouse-Following Tooltip */
  .audit-mini-tooltip-floating {
    background: rgba(15, 23, 42, 0.88) !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    padding: 3px 8px !important;
    border-radius: 6px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18) !important;
    backdrop-filter: blur(4px) !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    white-space: nowrap !important;
  }
  /* Anomaly Highlighted Rows on Main RAB Table */
  .rab-row-item > td {
    transition: background-color 0.25s ease, box-shadow 0.25s ease !important;
  }
  .rab-row-anomaly-critical > td {
    background-color: #fee2e2 !important;
  }
  .rab-row-anomaly-warning > td {
    background-color: #fef3c7 !important;
  }

  /* Active Hover & Highlight State Matching Static Status Colors (Like Gambar Kedua) */
  .rab-row-item.rab-row-hover-active > td {
    transition: background-color 0.25s ease, box-shadow 0.25s ease !important;
  }
  .rab-row-item.rab-row-hover-active.rab-row-anomaly-critical > td,
  .rab-row-flash-red > td {
    background-color: #fecaca !important;
    box-shadow: inset 0 2px 0 #dc2626, inset 0 -2px 0 #dc2626 !important;
    animation: pulseRowRed 1.8s ease-in-out infinite;
  }
  .rab-row-item.rab-row-hover-active.rab-row-anomaly-critical > td:first-child,
  .rab-row-flash-red > td:first-child {
    box-shadow: inset 2px 0 0 #dc2626, inset 0 2px 0 #dc2626, inset 0 -2px 0 #dc2626 !important;
  }
  .rab-row-item.rab-row-hover-active.rab-row-anomaly-critical > td:last-child,
  .rab-row-flash-red > td:last-child {
    box-shadow: inset -2px 0 0 #dc2626, inset 0 2px 0 #dc2626, inset 0 -2px 0 #dc2626 !important;
  }

  .rab-row-item.rab-row-hover-active.rab-row-anomaly-warning > td,
  .rab-row-flash-amber > td {
    background-color: #fef08a !important;
    box-shadow: inset 0 2px 0 #d97706, inset 0 -2px 0 #d97706 !important;
    animation: pulseRowAmber 1.8s ease-in-out infinite;
  }
  .rab-row-item.rab-row-hover-active.rab-row-anomaly-warning > td:first-child,
  .rab-row-flash-amber > td:first-child {
    box-shadow: inset 2px 0 0 #d97706, inset 0 2px 0 #d97706, inset 0 -2px 0 #d97706 !important;
  }
  .rab-row-item.rab-row-hover-active.rab-row-anomaly-warning > td:last-child,
  .rab-row-flash-amber > td:last-child {
    box-shadow: inset -2px 0 0 #d97706, inset 0 2px 0 #d97706, inset 0 -2px 0 #d97706 !important;
  }

  .rab-row-item.rab-row-hover-active.rab-row-flash-green > td,
  .rab-row-flash-green > td {
    background-color: #bbf7d0 !important;
    box-shadow: inset 0 2px 0 #16a34a, inset 0 -2px 0 #16a34a !important;
    animation: pulseRowGreen 1.8s ease-in-out infinite;
  }
  .rab-row-item.rab-row-hover-active.rab-row-flash-green > td:first-child,
  .rab-row-flash-green > td:first-child {
    box-shadow: inset 2px 0 0 #16a34a, inset 0 2px 0 #16a34a, inset 0 -2px 0 #16a34a !important;
  }
  .rab-row-item.rab-row-hover-active.rab-row-flash-green > td:last-child,
  .rab-row-flash-green > td:last-child {
    box-shadow: inset -2px 0 0 #16a34a, inset 0 2px 0 #16a34a, inset 0 -2px 0 #16a34a !important;
  }

  @keyframes pulseRowRed {
    0%, 100% { background-color: #fecaca; }
    50% { background-color: #fee2e2; }
  }
  @keyframes pulseRowAmber {
    0%, 100% { background-color: #fef08a; }
    50% { background-color: #fef9c3; }
  }
  @keyframes pulseRowGreen {
    0%, 100% { background-color: #bbf7d0; }
    50% { background-color: #dcfce7; }
  }

  /* Radar Pulse Loader Animation */
  .audit-radar-pulse {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: rgba(8, 127, 35, 0.1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #087f23;
    font-size: 28px;
    position: relative;
    animation: pulseRing 1.5s infinite ease-out;
  }
  @keyframes pulseRing {
    0% { box-shadow: 0 0 0 0 rgba(8, 127, 35, 0.4); }
    70% { box-shadow: 0 0 0 18px rgba(8, 127, 35, 0); }
    100% { box-shadow: 0 0 0 0 rgba(8, 127, 35, 0); }
  }

  .audit-ribbon-card {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    cursor: pointer;
    user-select: none;
  }
  .audit-ribbon-card:hover {
    border-color: #087f23;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(8, 127, 35, 0.12);
  }
  .audit-ribbon-card[data-category="critical"]:hover {
    border-color: #dc2626 !important;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15) !important;
  }
  .audit-ribbon-card[data-category="warning"]:hover {
    border-color: #d97706 !important;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.15) !important;
  }
  .audit-ribbon-card.audit-card-selected {
    border-width: 2px !important;
  }
  .audit-ribbon-card[data-category="critical"].audit-card-selected {
    border-color: #dc2626 !important;
    background-color: #fffaf0 !important;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.18) !important;
  }
  .audit-ribbon-card[data-category="warning"].audit-card-selected {
    border-color: #d97706 !important;
    background-color: #fffdf5 !important;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.18) !important;
  }
  .audit-card-header {
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
  }
  .audit-ribbon-icon-box {
    width: 36px;
    height: 36px;
    background-color: #087f23;
    color: #ffffff;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(8, 127, 35, 0.18);
  }
  .card-critical .audit-ribbon-icon-box {
    background-color: #dc2626;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
  }
  .card-warning .audit-ribbon-icon-box {
    background-color: #d97706;
    box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);
  }
  .audit-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
  }
  .audit-card-subtitle {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 1px;
  }
  .audit-card-body {
    padding: 12px 14px;
    border-top: 1px dashed #e2e8f0;
    background-color: #ffffff;
    font-size: 12px;
    color: #334155;
  }
  .badge-critical {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #f87171;
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
  }
  .badge-warning-custom {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fcd34d;
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
  }
  .badge-success-custom {
    background-color: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
  }

  /* AI Light Chat Container */
  .ai-panel-body-container {
    flex-grow: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: #ffffff;
  }
  .ai-chat-container {
    height: 100%;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background-color: #ffffff;
    padding: 24px 20px 16px 20px;
    color: #1e293b;
  }

  /* Vertical Action Chips */
  .ai-chip-btn {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 11.5px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 6px;
    width: fit-content;
    max-width: 100%;
    text-align: left;
  }
  .ai-chip-btn:hover {
    background-color: #ecfdf5;
    border-color: #86efac;
    color: #065f46;
  }
  .ai-chip-icon {
    font-size: 12px;
    color: #087f23;
    flex-shrink: 0;
  }

  /* Bottom Input Box */
  .ai-input-card {
    background-color: #1e1f22;
    border: 1px solid #2e3035;
    border-radius: 16px;
    padding: 14px;
  }
  .ai-input-field {
    background: transparent;
    border: none;
    color: #f1f5f9;
    font-size: 13.5px;
    width: 100%;
    outline: none;
    margin-bottom: 10px;
  }
  /* Usulan Perubahan Card (Separated Card) */
  .ai-proposal-card {
    border: 1.5px solid #cbd5e1;
    background-color: #ffffff;
    border-radius: 14px;
    padding: 14px 16px;
    margin-top: 8px;
    margin-bottom: 12px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .ai-proposal-card:hover {
    border-color: #087f23 !important;
    box-shadow: 0 4px 14px rgba(8, 127, 35, 0.12);
  }
  .ai-proposal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
  }
  .ai-proposal-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .ai-proposal-cost-badge {
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 6px;
    white-space: nowrap;
  }
  .ai-proposal-cost-positive {
    background-color: #fee2e2;
    color: #dc2626;
  }
  .ai-proposal-cost-negative {
    background-color: #dcfce7;
    color: #15803d;
  }
  .ai-proposal-cost-neutral {
    background-color: #f1f5f9;
    color: #475569;
  }
  .ai-proposal-item {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    user-select: none;
  }
  .ai-proposal-item:hover {
    background-color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  }
  .ai-proposal-item.item-ubah:hover {
    border-color: #f59e0b;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.12);
  }
  .ai-proposal-item.item-tambah:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.12);
  }
  .ai-proposal-item.item-hapus:hover {
    border-color: #ef4444;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.12);
  }
  .ai-proposal-item-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    display: inline-block;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .ai-badge-ubah {
    background-color: #fef3c7;
    color: #b45309;
  }
  .ai-badge-tambah {
    background-color: #dcfce7;
    color: #15803d;
  }
  .ai-badge-hapus {
    background-color: #fee2e2;
    color: #b91c1c;
  }
  .ai-proposal-item:hover .ai-badge-ubah {
    background-color: #fde68a;
  }
  .ai-proposal-item:hover .ai-badge-tambah {
    background-color: #bbf7d0;
  }
  .ai-proposal-item:hover .ai-badge-hapus {
    background-color: #fecaca;
  }
  /* Individual Acc button & proposal item checkbox */
  .ai-btn-acc-single {
    background-color: #087f23;
    border: 1px solid #087f23;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    transition: all 0.15s ease;
    cursor: pointer;
  }
  .ai-btn-acc-single:hover {
    background-color: #06691c;
    border-color: #06691c;
    transform: scale(1.04);
  }
  .ai-item-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    border-color: #94a3b8;
    flex-shrink: 0;
  }
  .ai-item-checkbox:checked {
    background-color: #087f23;
    border-color: #087f23;
  }
  .ai-proposal-item.applied {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    opacity: 0.82;
  }
  .ai-proposal-actions-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
  }
  .ai-btn-apply {
    background-color: #087f23;
    border-color: #087f23;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 9999px;
    flex-grow: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
    border: 1px solid #087f23;
  }
  .ai-btn-apply:hover {
    background-color: #06691c;
    border-color: #06691c;
    color: #ffffff;
  }
  .ai-btn-cancel {
    background-color: transparent;
    border: 1px solid #64748b;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 9999px;
    transition: all 0.15s ease;
  }
  .ai-btn-cancel:hover {
    background-color: #f1f5f9;
    color: #1e293b;
    border-color: #475569;
  }

  /* ------------------------------------------------------------- */
  /* TABLE DESIGN (1:1 DENGAN SCREENSHOT REFERENSI)               */
  /* ------------------------------------------------------------- */
  .rab-table-wrapper {
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 100vh;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
  }
  .rab-table-wrapper::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  .rab-table-wrapper::-webkit-scrollbar-track {
    background: #f8fafc;
  }
  .rab-table-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }

  .rab-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    margin-bottom: 0;
  }

  /* Sticky Thead: Solid Dark Green */
  .rab-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #087f23;
  }
  .rab-table th {
    background-color: #087f23 !important;
    color: #ffffff !important;
    font-size: 12px;
    font-weight: 700;
    padding: 10px 14px;
    letter-spacing: 0.02em;
    border: none;
    white-space: nowrap;
    user-select: none;
  }

  .rab-table td {
    padding: 10px 14px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Category Header Row */
  .rab-sec-row {
    background-color: #ffffff;
    transition: background-color 0.15s ease;
  }
  .rab-sec-row:hover {
    background-color: #f0f7ec;
  }
  .rab-sec-title {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12.5px;
    color: #1e293b;
    letter-spacing: 0.03em;
    cursor: pointer;
    user-select: none;
  }

  /* Toggle Collapse Circle (Red) */
  .btn-toggle-sec {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background-color: #d32f2f;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    cursor: pointer;
    padding: 0;
    margin: 0 auto;
    transition: transform 0.1s ease, background-color 0.15s ease;
    box-shadow: 0 1px 2px rgba(211, 47, 47, 0.25);
  }
  .btn-toggle-sec:hover {
    background-color: #b71c1c;
  }
  .btn-toggle-sec:active {
    transform: scale(0.95);
  }
  .minus-line {
    width: 8px;
    height: 2px;
    background-color: #ffffff;
    border-radius: 9999px;
    display: block;
  }
  .plus-icon {
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
    display: block;
  }

  /* Category Action Buttons (+ and Trash in Green) */
  .btn-sec-action {
    width: 22px;
    height: 22px;
    border-radius: 4px;
    background-color: #689f38;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .btn-sec-action:hover {
    background-color: #558b2f;
  }

  /* Item Rows */
  .rab-row-item {
    background-color: rgba(248, 250, 252, 0.55);
    transition: background-color 0.15s ease;
  }
  .rab-row-item:hover {
    background-color: #f1f5f9;
  }
  .rab-item-name {
    font-weight: 600;
    font-size: 12px;
    color: #1e293b;
  }

  /* Item Action Icons (Edit, Book, Trash) */
  .btn-item-icon {
    background: none;
    border: none;
    padding: 3px 5px;
    border-radius: 4px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    text-decoration: none;
  }
  .btn-item-icon.edit-icon {
    color: #64748b;
  }
  .btn-item-icon.edit-icon:hover {
    color: #1e293b;
    background-color: #e2e8f0;
  }
  .btn-item-icon.book-icon {
    color: #047857;
  }
  .btn-item-icon.book-icon:hover {
    color: #065f46;
    background-color: #ecfdf5;
  }
  .btn-item-icon.trash-icon {
    color: #ef4444;
  }
  .btn-item-icon.trash-icon:hover {
    color: #dc2626;
    background-color: #fee2e2;
  }

  /* Tabular Numerics */
  .tab-num {
    font-variant-numeric: tabular-nums;
  }

  /* Sticky Footer Solid Green */
  .rab-table tfoot {
    position: sticky;
    bottom: -1px;
    z-index: 10;
    background-color: #087f23 !important;
    color: #ffffff !important;
    user-select: none;
    box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.05);
  }
  .rab-table tfoot tr {
    background-color: #087f23 !important;
    border-top: 1px solid rgba(0, 110, 36, 0.35);
  }
  .rab-table tfoot td {
    background-color: #087f23 !important;
    color: #ffffff !important;
    font-weight: 700;
    font-size: 12px;
    padding: 10px 14px;
    border: none;
  }
  .rab-table tfoot tr.total-row td {
    font-weight: 800;
    font-size: 12.5px;
    padding: 11px 14px;
  }

  /* Print Optimization */
  @media print {
    .app-navbar, .rab-search-box, .no-print, .btn-item-icon, .btn-sec-action, .btn-toggle-sec, .ai-side-panel {
      display: none !important;
    }
    .anggaran-card {
      border: none !important;
      box-shadow: none !important;
      padding: 0 !important;
      margin: 0 !important;
    }
    .rab-table-wrapper {
      max-height: none !important;
      border: 1px solid #000000 !important;
    }
    .rab-table th, .rab-table tfoot {
      background-color: #087f23 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Banner -->
<div class="anggaran-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Estimator" class="anggaran-banner-bg" onerror="this.style.display='none'">
  <h1 class="anggaran-banner-title"><?= esc($project['title']) ?></h1>
</div>

<!-- Main Workspace Container -->
<main class="container-fluid px-3 px-md-5 my-4" style="max-width: 1440px;">
  <div class="anggaran-card" id="rabMainCard">

    <!-- Top Control Toolbar (Matching Screenshot Design 100%) -->
    <div class="rab-toolbar-container no-print">
      <div class="btn-pill-group">
        <button type="button" class="btn-pill-action" onclick="toggleAllCategoryCollapse()" title="Tutup / Buka semua kategori">
          <i class="bi bi-arrows-collapse"></i> COLLAPSE URAIAN
        </button>
        <button type="button" class="btn-pill-action" title="Atur urutan uraian pekerjaan">
          <i class="bi bi-list-task"></i> ATUR URUTAN URAIAN
        </button>
        <button type="button" class="btn-pill-action" title="Ubah kategori pekerjaan">
          <i class="bi bi-pencil-square"></i> UBAH KATEGORI
        </button>
        <button type="button" class="btn-pill-action" title="Impor volume pekerjaan">
          <i class="bi bi-upload"></i> IMPOR VOLUME
        </button>
        <button type="button" class="btn-pill-action" onclick="openAiAssistantPanel()" title="Tanya Asisten AI">
          <i class="bi bi-stars"></i> AUDIT & KONSULTASI
        </button>
      </div>

      <div class="rab-search-box">
        <span class="rab-search-label">Cari Data:</span>
        <input 
          type="text" 
          id="rabSearchInput" 
          placeholder="Masukkan kata kunci..." 
          class="rab-search-input"
          oninput="handleRabSearch(this.value)"
        >
      </div>
    </div>

    <!-- Table Container -->
    <div class="rab-table-wrapper">
      <table class="rab-table" id="rabMainTable">
        <!-- Thead: Solid Dark Green Header -->
        <thead>
          <tr>
            <th scope="col" class="text-center" style="width: 48px;">No.</th>
            <th scope="col" class="text-start" style="min-width: 280px;">Uraian Pekerjaan</th>
            <th scope="col" class="text-center" style="width: 96px;">Volume</th>
            <th scope="col" class="text-center" style="width: 80px;">Satuan</th>
            <th scope="col" class="text-end" style="width: 130px;">Harga Satuan</th>
            <th scope="col" class="text-end" style="width: 130px;">Harga</th>
            <th scope="col" class="text-end" style="width: 80px;">%</th>
            <th scope="col" class="text-center" style="width: 96px;">Aksi</th>
          </tr>
        </thead>

        <!-- Tbody: Categories & Sub-items -->
        <tbody id="rabTableBody">
          <?php if (empty($sections)): ?>
            <tr>
              <td colspan="8" class="py-5 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada data pekerjaan untuk proyek ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sections as $sIdx => $sec): ?>
              <!-- Category / Section Header Row -->
              <tr class="rab-sec-row" data-sec-id="<?= esc($sec['id']) ?>" data-sec-code="<?= esc($sec['code']) ?>" data-sec-name="<?= esc(strtolower($sec['name'] ?? '')) ?>">
                <!-- Col 1: Red Minus/Plus Circle Toggle -->
                <td class="text-center">
                  <button 
                    type="button" 
                    class="btn-toggle-sec" 
                    onclick="toggleCategoryCollapse('<?= esc($sec['code']) ?>')"
                    title="Buka / Tutup rincian pekerjaan kategori ini"
                  >
                    <span class="minus-line" id="minus-line-<?= esc($sec['code']) ?>"></span>
                    <span class="plus-icon d-none" id="plus-icon-<?= esc($sec['code']) ?>">+</span>
                  </button>
                </td>

                <!-- Col 2: Category Title -->
                <td class="rab-sec-title" onclick="toggleCategoryCollapse('<?= esc($sec['code']) ?>')">
                  <?= esc($sec['name']) ?>
                </td>

                <!-- Col 3: Volume (Blank) -->
                <td></td>

                <!-- Col 4: Satuan (Blank) -->
                <td></td>

                <!-- Col 5: Harga Satuan (Blank) -->
                <td></td>

                <!-- Col 6: Harga (Section Subtotal) -->
                <td class="text-end fw-medium text-dark tab-num" style="font-size: 12px;" id="sec-subtotal-<?= esc($sec['code']) ?>">
                  Rp <?= number_format($sec['subtotal'], 2, ',', '.') ?>
                </td>

                <!-- Col 7: % (Section Bobot) -->
                <td class="text-end fw-medium text-dark tab-num" style="font-size: 12px;" id="sec-bobot-<?= esc($sec['code']) ?>">
                  <?= number_format($sec['bobot'], 2, ',', '.') ?> %
                </td>

                <!-- Col 8: Aksi (Green + and Trash buttons) -->
                <td class="text-center">
                  <div class="d-inline-flex align-items-center justify-content-center gap-1">
                    <button 
                      type="button" 
                      class="btn-sec-action" 
                      onclick="openAddRabItemModal('<?= esc($sec['id']) ?>', '<?= esc($sec['code']) ?>')" 
                      title="Tambah Pekerjaan"
                    >
                      <i class="bi bi-plus" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn-sec-action" 
                      onclick="confirmDeleteSection('<?= esc($sec['id']) ?>', '<?= esc(addslashes($sec['name'])) ?>')" 
                      title="Hapus Kategori"
                    >
                      <i class="bi bi-trash" style="font-size: 12px; line-height: 1;"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Sub-item Rows in this Category -->
              <?php foreach ($sec['items'] as $iIdx => $it): ?>
                <?php 
                  $displayAhspCode = (!empty($it['ahsp_code']) && $it['ahsp_code'] !== '-') 
                      ? $it['ahsp_code'] 
                      : generate_ahsp_code($it['name'] ?? '', $sec['name'] ?? '', $sec['code'] ?? '', $sIdx, $iIdx, $it['code'] ?? '');
                ?>
                <tr 
                  class="rab-row-item sec-items-<?= esc($sec['code']) ?>" 
                  data-sec-code="<?= esc($sec['code']) ?>"
                  data-sec-name="<?= esc(strtolower($sec['name'] ?? '')) ?>"
                  data-item-id="<?= esc($it['id']) ?>"
                  data-item-name="<?= esc($it['ahsp_name'] ?: $it['name']) ?>"
                  data-raw-name="<?= esc(strtolower($it['name'] ?? '')) ?>"
                  data-ahsp-name="<?= esc(strtolower($it['ahsp_name'] ?? '')) ?>"
                  data-ahsp-code="<?= esc(strtolower($displayAhspCode)) ?>"
                  data-item-no="<?= esc(strtolower($it['no'] ?? '')) ?>"
                  data-item-unit="<?= esc(strtolower($it['unit'] ?? '')) ?>"
                >
                  <!-- Col 1: Item No. -->
                  <td class="text-center text-muted fw-semibold tab-num" style="font-size: 11.5px;">
                    <?= esc($it['no']) ?>
                  </td>

                  <!-- Col 2: Uraian Pekerjaan -->
                  <td style="padding-left: 32px;">
                    <div class="d-flex align-items-center gap-1.5">
                      <span class="rab-item-name">
                        <?= esc($it['ahsp_name'] ?: $it['name']) ?>
                      </span>
                    </div>
                  </td>

                  <!-- Col 3: Volume -->
                  <td class="text-center text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= number_format($it['volume'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 4: Satuan -->
                  <td class="text-center text-muted" style="font-size: 12px;">
                    <?= esc($it['unit']) ?>
                  </td>

                  <!-- Col 5: Harga Satuan -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    Rp <?= number_format($it['unit_price'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 6: Harga (Total) -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    Rp <?= number_format($it['subtotal'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 7: % (Bobot) -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= number_format($it['bobot'], 2, ',', '.') ?> %
                  </td>

                  <!-- Col 8: Aksi (Edit, Book/AHSP, Trash) -->
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                      <!-- Pemetaan AHSP -->
                      <a 
                        href="<?= base_url('pemetaan-ahsp') ?>?id=<?= urlencode($project['uuid'] ?: $project['id']) ?>&item=<?= urlencode($it['id']) ?>" 
                        class="btn-item-icon book-icon" 
                        title="Pemetaan AHSP"
                      >
                        <i class="bi bi-book" style="font-size: 13.5px;"></i>
                      </a>

                      <!-- Hapus Item -->
                      <button 
                        type="button" 
                        class="btn-item-icon trash-icon" 
                        onclick="confirmDeleteItem('<?= esc($it['id']) ?>', '<?= esc(addslashes($it['name'])) ?>')" 
                        title="Hapus Item"
                      >
                        <i class="bi bi-trash" style="font-size: 13.5px;"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>

        <!-- Tfoot: Solid Dark Green Totals -->
        <?php 
          $jumlahHarga = $grandTotal ?? 0;
          $ppnRateVal = $ppnRate ?? 0;
          $ppnAmount = $jumlahHarga * ($ppnRateVal / 100);
          $totalHarga = $jumlahHarga + $ppnAmount;
        ?>
        <tfoot>
          <!-- Row 1: JUMLAH HARGA -->
          <tr>
            <td colspan="5" class="text-end text-uppercase fw-bold text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              JUMLAH HARGA
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" id="tfootJumlahHarga" style="background-color: #087f23 !important;">
              Rp <?= number_format($jumlahHarga, 2, ',', '.') ?>
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" style="background-color: #087f23 !important;">
              100,00 %
            </td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>

          <!-- Row 2: PPN 0.00 % -->
          <tr>
            <td colspan="5" class="text-end text-uppercase fw-bold text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              PPN <?= number_format($ppnRate, 2, '.', '') ?> %
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" id="tfootPpnAmount" style="background-color: #087f23 !important;">
              Rp <?= number_format($ppnAmount, 2, ',', '.') ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>

          <!-- Row 3: TOTAL HARGA -->
          <tr class="total-row">
            <td colspan="5" class="text-end text-uppercase fw-bolder text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              TOTAL HARGA
            </td>
            <td class="text-end fw-bolder text-white tab-num pe-3" id="tfootTotalHarga" style="background-color: #087f23 !important;">
              Rp <?= number_format($totalHarga, 2, ',', '.') ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Backdrop Overlay -->
  <div class="ai-panel-backdrop d-none" id="aiSidePanelBackdrop" onclick="closeAiAssistantPanel()"></div>

  <!-- Right Workspace: Push AI Side Panel -->
  <div class="ai-side-panel d-none" id="aiSidePanel">
      <!-- Panel Header with Close Button -->
      <div class="panel-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background-color: #ecfdf5; color: #087f23;">
            <i class="bi bi-stars fs-6"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0" style="font-size: 14px;"> Asisten Estimator</h6>
          </div>
        </div>
        <button type="button" class="btn-close" onclick="closeAiAssistantPanel()" aria-label="Close"></button>
      </div>

      <!-- Nav Tabs -->
      <ul class="nav nav-tabs ai-nav-tabs" id="aiTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="aiTabAudit-tab" data-bs-toggle="tab" data-bs-target="#aiTabAudit" type="button" role="tab">
            <i class="bi bi-shield-check me-1"></i> Audit RAB
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="aiTabChat-tab" data-bs-toggle="tab" data-bs-target="#aiTabChat" type="button" role="tab">
            <i class="bi bi-chat-dots me-1"></i> Konsultasi RAB
          </button>
        </li>
      </ul>

      <!-- Panel Body Container -->
      <div class="p-0 ai-panel-body-container">
        <div class="tab-content h-100 d-flex flex-column" id="aiTabContent">
          
          <!-- TAB 1: RAB AUDIT -->
          <div class="tab-pane fade show active p-3 h-100 overflow-y-auto" id="aiTabAudit" role="tabpanel">
            
            <!-- STATE 1: INITIAL STATE -->
            <div id="auditStateInitial" class="d-flex flex-column justify-content-center h-100 pe-1" style="flex-grow: 1; min-height: 0;">
              <div class="my-auto">
                <div class="text-center mb-4">
                  <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 64px; height: 64px; background-color: #ecfdf5; color: #087f23; border: 1px solid #a7f3d0;">
                    <i class="bi bi-shield-check fs-1"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1" style="font-size: 20px;">Pemeriksaan Kelayakan</h5>
                  <p class="text-muted small px-2 mb-0" style="font-size: 12.5px; color: #64748b; line-height: 1.5;">
                    Pindai otomatis seluruh baris item pekerjaan RAB untuk menemukan anomali volume 0, ketidaklengkapan item AHSP, dan ketidakwajaran harga.
                  </p>
                </div>

                <div class="input-group align-items-center mt-4" style="background-color: transparent; padding: 0;">
                  <button type="button" class="btn btn-success rounded-3 w-100 fw-bold py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #087f23; border-color: #087f23; font-size: 13.5px; height: 48px; border-radius: 12px !important;" onclick="startAiScanProcess()">
                    <i class="bi bi-rocket-takeoff fs-6"></i>
                    <span> Mulai Pindai Audit RAB</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- STATE 2: LOADING STATE -->
            <div id="auditStateLoading" class="d-none flex-column align-items-center justify-content-center h-100 py-5 text-center">
              <div class="audit-radar-pulse mb-3">
                <i class="bi bi-search"></i>
              </div>
              <h6 class="fw-bold text-dark mb-1">Memindai Item RAB...</h6>
              <p class="text-muted small mb-3" style="font-size: 12px;">Menganalisis baris pekerjaan & memverifikasi kelengkapan data...</p>
              <div class="progress w-75 shadow-sm" style="height: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%;"></div>
              </div>
            </div>

            <!-- STATE 3: RESULT STATE -->
            <div id="auditStateResult" class="d-none flex-column h-100">
              
              <!-- Dynamic Floating Mouse Tooltip -->
              <div id="auditDynamicTooltip" class="audit-mini-tooltip-floating d-none" style="position: fixed; z-index: 99999; pointer-events: none; transition: opacity 0.12s ease-out;">
                <span id="auditDynamicTooltipText">1 item</span>
              </div>

              <!-- Top Control Header -->
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark" style="font-size: 13.5px;">Ringkasan Hasil Audit</span>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 11px;" onclick="startAiScanProcess()">
                  <i class="bi bi-arrow-repeat me-1"></i> Pindai Ulang
                </button>
              </div>

              <!-- Top Dynamic Donut Progress & Summary Card -->
              <div class="audit-circle-card mb-3">
                <div class="d-flex align-items-center gap-3">
                  <div style="position: relative; width: 68px; height: 68px; flex-shrink: 0;" id="auditCircleBadge">
                    <svg width="68" height="68" viewBox="0 0 68 68" style="transform: rotate(-90deg); overflow: visible;">
                      <!-- Red Slice -->
                      <circle id="donutSliceRed" class="donut-slice-item" cx="34" cy="34" r="26" fill="none" stroke="#dc2626" stroke-width="12" 
                              stroke-dasharray="8.17 163.36" stroke-dashoffset="0"
                              onclick="scrollToRabItemByText('', 'critical')"
                              data-tooltip-title="1 item"></circle>
                      
                      <!-- Yellow Slice -->
                      <circle id="donutSliceYellow" class="donut-slice-item" cx="34" cy="34" r="26" fill="none" stroke="#d97706" stroke-width="12" 
                              stroke-dasharray="8.17 163.36" stroke-dashoffset="-8.17"
                              onclick="scrollToRabItemByText('', 'warning')"
                              data-tooltip-title="1 item"></circle>

                      <!-- Green Slice -->
                      <circle id="donutSliceGreen" class="donut-slice-item" cx="34" cy="34" r="26" fill="none" stroke="#087f23" stroke-width="12" 
                              stroke-dasharray="147.02 163.36" stroke-dashoffset="-16.34"
                              onclick="scrollToRabItemByText('', 'normal')"
                              data-tooltip-title="18 item"></circle>
                    </svg>
                    <div id="auditPercentText" style="position: absolute; top: 0; left: 0; width: 68px; height: 68px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; color: #087f23; pointer-events: none;">
                      90%
                    </div>
                  </div>
                  <div>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 13px;">Status Kelayakan Item</h6>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <span class="badge-critical" id="badgeCountCritical" onclick="scrollToRabItemByText('', 'critical')" style="cursor: pointer;">- Kritis</span>
                      <span class="badge-warning-custom" id="badgeCountWarning" onclick="scrollToRabItemByText('', 'warning')" style="cursor: pointer;">- Peringatan</span>
                      <span class="badge-success-custom" id="badgeCountNormal" onclick="scrollToRabItemByText('', 'normal')" style="cursor: pointer;">- Normal</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Top Alert Banner -->
              <div class="p-2 mb-3 d-flex align-items-center gap-2" style="background-color: #fef9c3; border: 1px solid #fde047; border-radius: 10px; color: #854d0e;">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 20px; height: 20px; background-color: #d97706; color: #ffffff;">
                  <i class="bi bi-info-lg" style="font-size: 11px; font-weight: 800; line-height: 1; display: flex; align-items: center; justify-content: center;"></i>
                </div>
                <div style="font-weight: 500; color: #854d0e; font-size: 11.5px; line-height: 1.45;">
                  Klik pada <strong>kolom peringatan</strong> di bawah ini untuk langsung menuju baris item terkait di <strong>tabel RAB</strong>.
                </div>
              </div>

              <!-- Warning List Items -->
              <div id="auditWarningList" class="mb-3">
              </div>

            </div>

          </div>

          <!-- TAB 2: RAB ASSISTANT -->
          <div class="tab-pane fade h-100" id="aiTabChat" role="tabpanel">
            <div class="p-3 d-flex flex-column justify-content-between h-100" style="background-color: #ffffff; color: #1e293b; overflow: hidden;">
              
              <!-- STATE A: INITIAL CENTER LANDING VIEW -->
              <div id="aiInitialCenterArea" class="d-flex flex-column justify-content-center h-100 pe-1" style="flex-grow: 1; min-height: 0;">
                <div class="my-auto">
                  <div class="mb-3 text-start">
                    <h5 class="fw-bold text-dark mb-1" style="font-size: 20px;">
                      <i class="bi bi-stars text-success me-1.5" style="color: #087f23 !important;"></i>&nbsp Ruang Konsultasi
                    </h5>
                    <p class="text-muted small mb-0" style="font-size: 12.5px; color: #64748b;">
                      Tanyakan kelayakan RAB, estimasi biaya, atau minta rekomendasi item pekerjaan.
                    </p>
                  </div>

                  <!-- Action Chips -->
                  <div class="mb-3 d-flex flex-column align-items-start">
                    <span class="text-uppercase fw-bold d-block mb-1.5" style="font-size: 10px; color: #64748b; letter-spacing: 0.05em;">Pilihan Pertanyaan Cepat:</span>
                    
                    <button type="button" class="ai-chip-btn" onclick="sendAiPrompt('Analisis kelengkapan item pekerjaan RAB ini')">
                      <i class="bi bi-card-checklist ai-chip-icon"></i>
                      <span>Cek kelengkapan item pekerjaan RAB</span>
                    </button>

                    <button type="button" class="ai-chip-btn" onclick="sendAiPrompt('Evaluasi kewajaran volume & harga satuan')">
                      <i class="bi bi-calculator ai-chip-icon"></i>
                      <span>Evaluasi kewajaran volume & harga</span>
                    </button>

                    <button type="button" class="ai-chip-btn" onclick="sendAiPrompt('Apa kebutuhan pekerjaan untuk membuat garasi?')">
                      <i class="bi bi-house-add ai-chip-icon"></i>
                      <span>Rekomendasi kebutuhan garasi & struktur</span>
                    </button>
                  </div>

                  <!-- Central Input Box Container -->
                  <div class="input-group align-items-center" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 6px 8px;">
                    <input 
                      type="text" 
                      id="aiChatInputCenter" 
                      class="form-control bg-transparent text-dark border-0 shadow-none px-3" 
                      placeholder="Ketik pertanyaan tentang RAB ini..." 
                      style="color: #1e293b !important; font-size: 13.5px;"
                      onkeypress="handleAiKeyPressCenter(event)"
                    >
                    <button 
                      type="button" 
                      class="btn p-0 d-flex align-items-center justify-content-center border-0 ms-1" 
                      style="background-color: #087f23; color: #ffffff; width: 36px; height: 36px; border-radius: 8px; transition: background-color 0.2s ease;"
                      onmouseover="this.style.backgroundColor='#06691c';"
                      onmouseout="this.style.backgroundColor='#087f23';"
                      onclick="sendAiChatMessageFromCenter()"
                      title="Submit"
                      data-bs-toggle="tooltip"
                      data-bs-placement="top"
                    >
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" class="text-white" style="transform: translateX(1px);">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- STATE B: ACTIVE CHAT HEADER BAR -->
              <div id="aiActiveChatHeader" class="d-none align-items-center justify-content-between pb-2 mb-1 border-bottom border-light-subtle">
                <span class="fw-bold text-dark d-flex align-items-center gap-1.5" style="font-size: 13px;">
                  <i class="bi bi-stars" style="color: #087f23;"></i> Riwayat Konsultasi
                </span>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill py-0.5 px-2.5" style="font-size: 11px;" onclick="clearAiChatHistory()" title="Bersihkan riwayat percakapan">
                  <i class="bi bi-trash me-1"></i> Bersihkan Chat
                </button>
              </div>

              <!-- STATE B: ACTIVE CHAT LOG -->
              <div id="aiChatHistoryLog" class="d-none pe-1 my-2" style="flex-grow: 1; overflow-y: auto; min-height: 0;">
              </div>

              <!-- STATE B: BOTTOM PINNED INPUT AREA -->
              <div id="aiBottomInputContainer" class="d-none pt-2 border-top border-light-subtle" style="flex-shrink: 0;">
                <div class="input-group align-items-center" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 4px 6px;">
                  <input 
                    type="text" 
                    id="aiChatInput" 
                    class="form-control bg-transparent text-dark border-0 shadow-none px-3" 
                    placeholder="Ketik pertanyaan tentang RAB ini..." 
                    style="color: #1e293b !important; font-size: 13px;"
                    onkeypress="handleAiKeyPress(event)"
                  >
                  <button 
                    type="button" 
                    class="btn p-0 d-flex align-items-center justify-content-center border-0 ms-1" 
                    style="background-color: #087f23; color: #ffffff; width: 34px; height: 34px; border-radius: 8px; transition: background-color 0.2s ease;"
                    onmouseover="this.style.backgroundColor='#06691c';"
                    onmouseout="this.style.backgroundColor='#087f23';"
                    onclick="sendAiChatMessage()"
                    title="Submit"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="text-white" style="transform: translateX(1px);">
                      <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                  </button>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>

</main>

<!-- Modal: Ubah Item -->
<div class="modal fade" id="editRabItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-dark text-white py-2.5 px-3">
        <h6 class="modal-title fs-6 fw-bold">
          <i class="bi bi-pencil-square me-1"></i> Ubah Item Pekerjaan
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="editRabItemId">
        <div class="mb-2">
          <label class="form-label text-muted small mb-1 fw-medium" id="editRabItemName">-</label>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold mb-1">Volume</label>
          <div class="input-group input-group-sm">
            <input type="number" step="0.01" class="form-control" id="editRabVolumeInput" min="0">
            <span class="input-group-text" id="editRabUnitSpan">m2</span>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Harga Satuan (Rp)</label>
          <input type="number" step="500" class="form-control form-control-sm" id="editRabPriceInput" min="0">
        </div>
      </div>
      <div class="modal-footer bg-light p-2 justify-content-end">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="saveRabItemEdit()" style="background-color: #00802b; border-color: #00802b;">
          Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Tambah Pekerjaan Baru -->
<div class="modal fade" id="addRabItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-success text-white py-2.5 px-3" style="background-color: #087f23 !important;">
        <h6 class="modal-title fs-6 fw-bold">
          <i class="bi bi-plus-circle me-1"></i> Tambah Pekerjaan
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="addRabSectionId">
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Uraian Pekerjaan</label>
          <input type="text" class="form-control form-control-sm" id="addRabItemNameInput" placeholder="Nama pekerjaan...">
        </div>
        <div class="row g-2 mb-2">
          <div class="col-7">
            <label class="form-label small fw-semibold mb-1">Volume</label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="addRabItemVolInput" value="1.00">
          </div>
          <div class="col-5">
            <label class="form-label small fw-semibold mb-1">Satuan</label>
            <input type="text" class="form-control form-control-sm" id="addRabItemUnitInput" value="m2">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Harga Satuan (Rp)</label>
          <input type="number" step="500" class="form-control form-control-sm" id="addRabItemPriceInput" value="0">
        </div>
      </div>
      <div class="modal-footer bg-light p-2 justify-content-end">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" onclick="executeAddRabItem()" style="background-color: #087f23; border-color: #087f23;">
          Tambah
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Konfirmasi Hapus -->
<div class="modal fade" id="deleteRabModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-body p-4 text-center">
        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
          <i class="bi bi-trash fs-4"></i>
        </div>
        <h6 class="fw-bold mb-2" id="deleteRabModalTitle">Hapus Pekerjaan Ini?</h6>
        <p class="text-secondary small mb-4" id="deleteRabModalDesc">Apakah Anda yakin ingin menghapus pekerjaan ini dari tabel RAB?</p>
        <input type="hidden" id="deleteRabTargetId">
        <input type="hidden" id="deleteRabTargetType">
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-light btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger btn-sm px-3 rounded-pill" onclick="executeDeleteRab()">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  let editModalInstance = null;
  let addModalInstance = null;
  let deleteModalInstance = null;
  const collapsedSections = {};

  document.addEventListener('DOMContentLoaded', () => {
    editModalInstance = new bootstrap.Modal(document.getElementById('editRabItemModal'));
    addModalInstance = new bootstrap.Modal(document.getElementById('addRabItemModal'));
    deleteModalInstance = new bootstrap.Modal(document.getElementById('deleteRabModal'));

    const chatTabEl = document.getElementById('aiTabChat-tab');
    if (chatTabEl) {
      chatTabEl.addEventListener('shown.bs.tab', () => {
        focusAiChatInput();
        clearRabAnomalyHighlights();
      });
    }

    const auditTabEl = document.getElementById('aiTabAudit-tab');
    if (auditTabEl) {
      auditTabEl.addEventListener('shown.bs.tab', () => {
        const resultState = document.getElementById('auditStateResult');
        if (resultState && !resultState.classList.contains('d-none')) {
          renderAuditResults();
        }
      });
    }

    initBootstrapTooltips();
    loadChatHistoryFromBackend();

    // Preserve Consultation tab if user previously opened it
    const savedAiTab = sessionStorage.getItem('activeAiTab');
    if (savedAiTab === 'chat') {
      openAiAssistantPanel();
    }
  });

  async function loadChatHistoryFromBackend() {
    try {
      const projectId = `<?= esc($project['id']) ?>`;
      const res = await fetch(`<?= base_url("api/projects") ?>/${projectId}/chat-history`);
      if (res.ok) {
        const data = await res.json();
        if (data.messages && data.messages.length > 0) {
          activateChatMode();
          const log = document.getElementById('aiChatHistoryLog');
          if (log) log.innerHTML = '';
          data.messages.forEach(m => {
            if (m.sender === 'user') {
              appendChatMessage('user', m.message);
            } else {
              let htmlReply = formatAiMarkdown(m.message);
              appendChatMessage('ai', htmlReply);
              // Hide proposed actions card if already applied
              if (m.actions && m.actions.length > 0 && !m.is_applied) {
                appendProposedActionsCard(m.actions, m.cost_impact, m.id, m.is_applied);
              }
            }
          });
          if (log) log.scrollTop = log.scrollHeight;
        }
      }
    } catch (e) {
      console.warn('Load chat history connection:', e);
    }
  }

  // Helper: Initialize Bootstrap Tooltips
  function initBootstrapTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  // Helper: Auto Focus Chat Input Field
  function focusAiChatInput() {
    setTimeout(() => {
      const centerArea = document.getElementById('aiInitialCenterArea');
      const isCenterVisible = centerArea && !centerArea.classList.contains('d-none');
      const inputToFocus = isCenterVisible 
        ? document.getElementById('aiChatInputCenter')
        : document.getElementById('aiChatInput');
      
      if (inputToFocus) {
        inputToFocus.focus();
      }
    }, 120);
  }

  // Open AI Assistant Side Panel
  function openAiAssistantPanel() {
    const sidePanel = document.getElementById('aiSidePanel');
    const backdrop = document.getElementById('aiSidePanelBackdrop');
    if (sidePanel) sidePanel.classList.remove('d-none');
    if (backdrop) backdrop.classList.remove('d-none');

    const chatTabEl = document.getElementById('aiTabChat-tab');
    if (chatTabEl) {
      const tab = new bootstrap.Tab(chatTabEl);
      tab.show();
    }
    sessionStorage.setItem('activeAiTab', 'chat');
    focusAiChatInput();
    clearRabAnomalyHighlights();
  }

  function openAiScanPanel() {
    const sidePanel = document.getElementById('aiSidePanel');
    const backdrop = document.getElementById('aiSidePanelBackdrop');
    if (sidePanel) sidePanel.classList.remove('d-none');
    if (backdrop) backdrop.classList.remove('d-none');

    const auditTabEl = document.getElementById('aiTabAudit-tab');
    if (auditTabEl) {
      const tab = new bootstrap.Tab(auditTabEl);
      tab.show();
    }

    const resultState = document.getElementById('auditStateResult');
    if (resultState && !resultState.classList.contains('d-none')) {
      renderAuditResults();
    }
  }

  function closeAiAssistantPanel() {
    const sidePanel = document.getElementById('aiSidePanel');
    const backdrop = document.getElementById('aiSidePanelBackdrop');
    if (sidePanel) sidePanel.classList.add('d-none');
    if (backdrop) backdrop.classList.add('d-none');

    sessionStorage.removeItem('activeAiTab');
    clearRabAnomalyHighlights();
  }

  function clearRabAnomalyHighlights() {
    const itemRows = document.querySelectorAll('#rabTableBody tr.rab-row-item');
    itemRows.forEach(row => {
      if (row._highlightTimer) clearTimeout(row._highlightTimer);
      row.classList.remove('rab-row-anomaly-critical', 'rab-row-anomaly-warning', 'rab-row-flash-red', 'rab-row-flash-amber', 'rab-row-flash-green', 'rab-row-hover-active');
    });
    document.querySelectorAll('.audit-ribbon-card').forEach(c => c.classList.remove('audit-card-selected'));
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.donut-slice-item').forEach(bindDynamicTooltip);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAiAssistantPanel();
    }
  });

  document.addEventListener('mousedown', (e) => {
    const sidePanel = document.getElementById('aiSidePanel');
    if (!sidePanel || sidePanel.classList.contains('d-none')) return;
    if (sidePanel.contains(e.target)) return;

    const triggerBtns = document.querySelectorAll('.btn-pill-action, [onclick*="openAiAssistantPanel"], [onclick*="openAiScanPanel"]');
    for (let b of triggerBtns) {
      if (b.contains(e.target)) return;
    }

    closeAiAssistantPanel();
  });

  // --- Extract Active RAB Data from DOM Table ---
  function collectRABData() {
    const items = [];
    let itemId = 0;
    let currentCategory = '';
    let currentSectionId = null;

    document.querySelectorAll('#rabTableBody tr').forEach(row => {
      if (row.classList.contains('rab-sec-row')) {
        const titleEl = row.querySelector('.rab-sec-title');
        currentCategory = titleEl ? titleEl.textContent.trim() : '';
        currentSectionId = parseInt(row.dataset.secId) || null;
        return;
      }
      if (!row.classList.contains('rab-row-item')) return;

      itemId++;
      const cells = row.querySelectorAll('td');
      if (cells.length < 7) return;

      const nameEl = row.querySelector('.rab-item-name');
      const description = nameEl ? nameEl.textContent.trim() : (row.dataset.itemName || '');
      const ahspCode = (row.dataset.ahspCode || '').replace('-', '');
      const volumeText = cells[2]?.textContent?.trim().replace(/\./g, '').replace(',', '.') || '0';
      const unit = cells[3]?.textContent?.trim() || row.dataset.itemUnit || '';
      const priceText = cells[4]?.textContent?.trim().replace(/Rp|\s|\./g, '').replace(',', '.') || '0';
      const totalText = cells[5]?.textContent?.trim().replace(/Rp|\s|\./g, '').replace(',', '.') || '0';

      items.push({
        id: parseInt(row.dataset.itemId) || itemId,
        section_id: currentSectionId,
        category: currentCategory,
        description: description,
        ahsp_code: ahspCode,
        volume: parseFloat(volumeText) || 0,
        unit: unit,
        unit_price: parseFloat(priceText) || 0,
        total_price: parseFloat(totalText) || 0,
      });
    });

    return items;
  }

  // --- RAB Audit Workflow & Table Highlighting Interactivity ---
  async function startAiScanProcess() {
    const initialState = document.getElementById('auditStateInitial');
    const loadingState = document.getElementById('auditStateLoading');
    const resultState = document.getElementById('auditStateResult');

    if (initialState) initialState.classList.add('d-none');
    if (resultState) resultState.classList.add('d-none');
    if (loadingState) {
      loadingState.classList.remove('d-none');
      loadingState.classList.add('d-flex');
    }

    try {
      await renderAuditResults();
    } catch (err) {
      console.warn('Audit process error:', err);
    } finally {
      if (loadingState) {
        loadingState.classList.add('d-none');
        loadingState.classList.remove('d-flex');
      }
      if (resultState) {
        resultState.classList.remove('d-none');
        resultState.classList.add('d-flex');
      }
    }
  }

  // --- Dynamic Floating Mouse Tooltip Logic ---
  function showDynamicTooltip(text, x, y) {
    const tooltip = document.getElementById('auditDynamicTooltip');
    const textEl = document.getElementById('auditDynamicTooltipText');
    if (!tooltip || !textEl) return;
    textEl.textContent = text;
    tooltip.style.left = (x + 10) + 'px';
    tooltip.style.top = (y - 28) + 'px';
    tooltip.classList.remove('d-none');
  }

  function moveDynamicTooltip(x, y) {
    const tooltip = document.getElementById('auditDynamicTooltip');
    if (!tooltip) return;
    tooltip.style.left = (x + 10) + 'px';
    tooltip.style.top = (y - 28) + 'px';
  }

  function hideDynamicTooltip() {
    const tooltip = document.getElementById('auditDynamicTooltip');
    if (!tooltip) return;
    tooltip.classList.add('d-none');
  }

  function bindDynamicTooltip(element) {
    if (!element || element.dataset.tooltipBound) return;
    element.dataset.tooltipBound = "true";

    element.addEventListener('mouseenter', (e) => {
      const title = element.getAttribute('data-tooltip-title') || '1 item';
      showDynamicTooltip(title, e.clientX, e.clientY);
    });
    element.addEventListener('mousemove', (e) => {
      moveDynamicTooltip(e.clientX, e.clientY);
    });
    element.addEventListener('mouseleave', () => {
      hideDynamicTooltip();
    });
    element.addEventListener('click', (e) => {
      const title = element.getAttribute('data-tooltip-title') || '1 item';
      showDynamicTooltip(title, e.clientX, e.clientY);
    });
  }

  function escapeJsString(str) {
    return (str || '')
      .replace(/\\/g, '\\\\')
      .replace(/'/g, "\\'")
      .replace(/"/g, '&quot;')
      .replace(/[\r\n\t]+/g, ' ')
      .trim();
  }

  async function renderAuditResults() {
    const items = collectRABData();
    const itemRows = document.querySelectorAll('#rabTableBody tr.rab-row-item');
    let totalItems = items.length || itemRows.length;
    let criticalCount = 0;
    let warningCount = 0;

    const warningListContainer = document.getElementById('auditWarningList');
    let warningCardsHTML = '';

    itemRows.forEach(r => r.classList.remove('rab-row-anomaly-critical', 'rab-row-anomaly-warning'));

    const projectId = `<?= esc($project['id']) ?>`;
    const payload = {
      project_id: projectId,
      project_context: {
        project_name: `<?= esc(addslashes($project['name'] ?? $project['title'] ?? 'Proyek RAB')) ?>`,
        building_type: `<?= esc(addslashes($project['building_type'] ?? 'Rumah Tinggal')) ?>`,
        location: { province: "Jawa Tengah", city_regency: "Banyumas" },
        building_area_m2: <?= (float)($project['building_area'] ?? 100.0) ?>,
        number_of_floors: <?= (int)($project['floors'] ?? 1) ?>,
        currency: "IDR"
      },
      items: items
    };

    let auditData = null;
    let auditErrorMsg = null;

    // 1. Call real AI RAB Audit endpoint
    try {
      const res = await fetch(`<?= base_url("api/v2/ai/rab-audit") ?>`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json().catch(() => ({}));
      if (res.ok && json.data && typeof json.data.health_score !== 'undefined') {
        auditData = json.data;
      } else {
        auditErrorMsg = json.message || `HTTP ${res.status}: Gagal memproses audit.`;
      }
    } catch (err) {
      console.error('AI RAB Audit API error:', err);
      auditErrorMsg = err.message || 'Koneksi ke AI Auditor terputus.';
    }

    if (auditErrorMsg || !auditData) {
      if (warningListContainer) {
        warningListContainer.innerHTML = `
          <div class="alert alert-danger d-flex align-items-center gap-2 p-3 rounded-3" style="font-size: 12.5px;">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0" style="font-size: 18px;"></i>
            <div>
              <strong>Gagal Menjalankan Audit AI:</strong><br>
              ${escapeHtml(auditErrorMsg || 'Layanan AI Auditor tidak dapat dihubungi.')}
            </div>
          </div>
        `;
      }
      const percentOverlay = document.getElementById('auditPercentText');
      if (percentOverlay) percentOverlay.textContent = '-';
      return;
    }

    // 2. Render real anomalies from API
    if (auditData.anomalies && auditData.anomalies.length > 0) {
      auditData.anomalies.forEach(an => {
        const isCrit = an.severity === 'CRITICAL';
        if (isCrit) criticalCount++; else warningCount++;

        const targetRow = Array.from(itemRows).find(r => {
          const rId = parseInt(r.getAttribute('data-item-id') || r.getAttribute('data-id'));
          return rId === an.item_id;
        });

        if (targetRow) {
          targetRow.classList.add(isCrit ? 'rab-row-anomaly-critical' : 'rab-row-anomaly-warning');
        }

        const rawName = targetRow ? (targetRow.querySelector('.rab-item-name')?.textContent || '') : '';
        const cleanItemName = rawName.replace(/[\r\n\t]+/g, ' ').trim() || `Item #${an.item_id}`;

        warningCardsHTML += `
          <div class="audit-ribbon-card mb-3" 
               data-item-id="${an.item_id}" 
               data-category="${isCrit ? 'critical' : 'warning'}"
               data-item-name="${escapeHtml(cleanItemName)}"
               onclick="handleAnomalyCardClick(this)">
            <div class="d-flex align-items-center gap-1.5 mb-1.5">
              <span style="font-size: 11px;">${isCrit ? '🔴' : '🟡'}</span>
              <span class="fw-bold" style="color: ${isCrit ? '#dc2626' : '#d97706'}; font-size: 11.5px; letter-spacing: 0.03em; text-transform: uppercase;">
                ${(an.type || 'ANOMALI').replace(/_/g, ' ')} · ITEM #${an.item_id}
              </span>
            </div>
            <div style="font-size: 12.5px; line-height: 1.5; color: #475569; margin-bottom: 8px;">
              ${escapeHtml(an.message)}
            </div>
            <div class="ps-2.5" style="border-left: 2px solid #cbd5e1; font-size: 11.5px; color: #64748b; line-height: 1.45;">
              💡 ${escapeHtml(an.recommendation || 'Periksa kembali perhitungan kuantitas dan dimensi volume pekerjaan.')}
            </div>
          </div>
        `;
      });
    }

    // 3. Render missing scopes from API if any
    if (auditData.missing_scopes && auditData.missing_scopes.length > 0) {
      auditData.missing_scopes.forEach(ms => {
        warningCount++;
        const catName = (ms.category || 'Pekerjaan').replace(/[\r\n\t]+/g, ' ').trim();
        warningCardsHTML += `
          <div class="audit-ribbon-card mb-3" style="border-left-color: #f59e0b;"
               data-category="warning"
               data-category-name="${escapeHtml(catName)}"
               onclick="handleMissingScopeCardClick(this)">
            <div class="d-flex align-items-center gap-1.5 mb-1.5">
              <span style="font-size: 11px;">⚠️</span>
              <span class="fw-bold" style="color: #d97706; font-size: 11.5px; letter-spacing: 0.03em; text-transform: uppercase;">
                MISSING SCOPE · ${escapeHtml(ms.category || 'Pekerjaan')}
              </span>
            </div>
            <div style="font-size: 12.5px; line-height: 1.5; color: #475569; margin-bottom: 8px;">
              <strong>${escapeHtml(ms.missing_item || '')}</strong>: ${escapeHtml(ms.reason || '')}
            </div>
          </div>
        `;
      });
    }

    if (warningListContainer) {
      if (warningCardsHTML) {
        warningListContainer.innerHTML = `
          <div class="fw-medium text-secondary mb-2" style="font-size: 13.5px; color: #475569;">Detail Anomali</div>
          ${warningCardsHTML}
        `;
      } else {
        warningListContainer.innerHTML = `
          <div class="p-3 text-center text-muted small bg-light rounded-3">
            <i class="bi bi-check-circle-fill text-success fs-5 d-block mb-1"></i>
            Seluruh item pekerjaan RAB telah memenuhi kriteria audit.
          </div>
        `;
      }
    }

    let flaggedCount = criticalCount + warningCount;
    let normalCount = Math.max(0, totalItems - flaggedCount);
    let normalPercent = totalItems > 0 ? Math.round((normalCount / totalItems) * 100) : 100;

    const percentOverlay = document.getElementById('auditPercentText');
    if (percentOverlay) {
      percentOverlay.textContent = `${normalPercent}%`;
    }

    const C = 2 * Math.PI * 26;

    const sliceRed = document.getElementById('donutSliceRed');
    const sliceYellow = document.getElementById('donutSliceYellow');
    const sliceGreen = document.getElementById('donutSliceGreen');

    if (totalItems > 0) {
      const lenRed = (criticalCount / totalItems) * C;
      const lenYellow = (warningCount / totalItems) * C;
      const lenGreen = (normalCount / totalItems) * C;

      if (sliceRed) {
        sliceRed.setAttribute('stroke-dasharray', `${lenRed.toFixed(2)} ${C.toFixed(2)}`);
        sliceRed.setAttribute('stroke-dashoffset', '0');
        sliceRed.setAttribute('data-tooltip-title', `${criticalCount} item`);
      }
      if (sliceYellow) {
        sliceYellow.setAttribute('stroke-dasharray', `${lenYellow.toFixed(2)} ${C.toFixed(2)}`);
        sliceYellow.setAttribute('stroke-dashoffset', `-${lenRed.toFixed(2)}`);
        sliceYellow.setAttribute('data-tooltip-title', `${warningCount} item`);
      }
      if (sliceGreen) {
        sliceGreen.setAttribute('stroke-dasharray', `${lenGreen.toFixed(2)} ${C.toFixed(2)}`);
        sliceGreen.setAttribute('stroke-dashoffset', `-${(lenRed + lenYellow).toFixed(2)}`);
        sliceGreen.setAttribute('data-tooltip-title', `${normalCount} item`);
      }
    }

    const badgeCrit = document.getElementById('badgeCountCritical');
    const badgeWarn = document.getElementById('badgeCountWarning');
    const badgeNorm = document.getElementById('badgeCountNormal');

    if (badgeCrit) {
      badgeCrit.textContent = `${criticalCount} Kritis`;
    }
    if (badgeWarn) {
      badgeWarn.textContent = `${warningCount} Peringatan`;
    }
    if (badgeNorm) {
      badgeNorm.textContent = `${normalCount} Normal`;
    }

    [sliceRed, sliceYellow, sliceGreen].forEach(bindDynamicTooltip);
  }

  function handleAnomalyCardClick(el) {
    if (!el) return;
    const itemId = parseInt(el.getAttribute('data-item-id')) || null;
    const category = el.getAttribute('data-category') || 'warning';
    const itemName = el.getAttribute('data-item-name') || '';

    document.querySelectorAll('.audit-ribbon-card').forEach(c => c.classList.remove('audit-card-selected'));
    el.classList.add('audit-card-selected');

    scrollToRabItemByText(itemName, category, itemId);
  }

  function handleMissingScopeCardClick(el) {
    if (!el) return;
    const catName = (el.getAttribute('data-category-name') || '').toLowerCase().trim();

    document.querySelectorAll('.audit-ribbon-card').forEach(c => c.classList.remove('audit-card-selected'));
    el.classList.add('audit-card-selected');

    if (!catName) return;

    const secRows = document.querySelectorAll('#rabTableBody tr.rab-sec-row');
    let targetSecRow = null;
    secRows.forEach(sr => {
      const name = (sr.getAttribute('data-sec-name') || sr.querySelector('.rab-sec-title')?.textContent || '').toLowerCase().trim();
      if (name.includes(catName) || catName.includes(name)) {
        targetSecRow = sr;
      }
    });

    if (targetSecRow) {
      const secCode = targetSecRow.getAttribute('data-sec-code');
      if (secCode) {
        const minusLine = document.getElementById('minus-line-' + secCode);
        const plusIcon = document.getElementById('plus-icon-' + secCode);
        const hiddenItems = document.querySelectorAll('.sec-items-' + secCode + '.d-none');
        if (hiddenItems.length > 0) {
          hiddenItems.forEach(item => item.classList.remove('d-none'));
          if (minusLine) minusLine.classList.remove('d-none');
          if (plusIcon) plusIcon.classList.add('d-none');
        }
      }
      targetSecRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
      targetSecRow.classList.remove('rab-row-flash-amber');
      void targetSecRow.offsetWidth;
      targetSecRow.classList.add('rab-row-flash-amber');
      setTimeout(() => targetSecRow.classList.remove('rab-row-flash-amber'), 2500);
    }
  }

  function scrollToRabItemByText(queryText, category, itemId = null) {
    const q = (queryText || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#rabTableBody tr.rab-row-item');

    rows.forEach(r => {
      if (r._highlightTimer) clearTimeout(r._highlightTimer);
      r.classList.remove('rab-row-flash-red', 'rab-row-flash-amber', 'rab-row-flash-green', 'rab-row-hover-active');
    });

    if (category === 'normal' && !q && !itemId) {
      const normalRows = [];
      rows.forEach(r => {
        if (!r.classList.contains('rab-row-anomaly-critical') && !r.classList.contains('rab-row-anomaly-warning')) {
          normalRows.push(r);
        }
      });

      if (normalRows.length > 0) {
        normalRows.forEach(r => {
          const secCode = r.getAttribute('data-sec-code');
          if (secCode) {
            const minusLine = document.getElementById('minus-line-' + secCode);
            const plusIcon = document.getElementById('plus-icon-' + secCode);
            const hiddenItems = document.querySelectorAll('.sec-items-' + secCode + '.d-none');
            if (hiddenItems.length > 0) {
              hiddenItems.forEach(el => el.classList.remove('d-none'));
              if (minusLine) minusLine.classList.remove('d-none');
              if (plusIcon) plusIcon.classList.add('d-none');
            }
          }
        });

        normalRows[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

        normalRows.forEach(r => {
          r.classList.add('rab-row-flash-green');
          r._highlightTimer = setTimeout(() => {
            r.classList.remove('rab-row-flash-green');
          }, 2500);
        });
      }
      return;
    }

    let targetRow = null;

    if (itemId) {
      targetRow = document.querySelector(`#rabTableBody tr.rab-row-item[data-item-id="${itemId}"]`);
    }

    if (!targetRow && q) {
      rows.forEach(r => {
        const nameSpan = r.querySelector('.rab-item-name');
        const rowName = (nameSpan ? nameSpan.textContent : (r.getAttribute('data-item-name') || '')).toLowerCase().trim();
        if (rowName.includes(q) || q.includes(rowName)) {
          targetRow = r;
        }
      });
    }

    if (!targetRow && category) {
      targetRow = document.querySelector(`#rabTableBody tr.rab-row-anomaly-${category}`);
    }

    if (!targetRow && rows.length > 0) {
      targetRow = rows[0];
    }

    if (targetRow) {
      const secCode = targetRow.getAttribute('data-sec-code');
      if (secCode) {
        const minusLine = document.getElementById('minus-line-' + secCode);
        const plusIcon = document.getElementById('plus-icon-' + secCode);
        const hiddenItems = document.querySelectorAll('.sec-items-' + secCode + '.d-none');
        
        if (hiddenItems.length > 0) {
          hiddenItems.forEach(el => el.classList.remove('d-none'));
          if (minusLine) minusLine.classList.remove('d-none');
          if (plusIcon) plusIcon.classList.add('d-none');
        }
      }

      targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

      const isCrit = category === 'critical' || targetRow.classList.contains('rab-row-anomaly-critical');
      const isWarn = category === 'warning' || targetRow.classList.contains('rab-row-anomaly-warning');

      const flashClass = isCrit ? 'rab-row-flash-red' : (isWarn ? 'rab-row-flash-amber' : 'rab-row-flash-green');
      
      targetRow.classList.remove('rab-row-flash-red', 'rab-row-flash-amber', 'rab-row-flash-green', 'rab-row-hover-active');
      void targetRow.offsetWidth;
      targetRow.classList.add(flashClass);

      targetRow._highlightTimer = setTimeout(() => {
        targetRow.classList.remove(flashClass);
      }, 2500);

      // Synchronize active card selection in Detail Anomali list
      const targetItemId = targetRow.getAttribute('data-item-id');
      if (targetItemId) {
        const matchedCard = document.querySelector(`.audit-ribbon-card[data-item-id="${targetItemId}"]`);
        if (matchedCard && !matchedCard.classList.contains('audit-card-selected')) {
          document.querySelectorAll('.audit-ribbon-card').forEach(c => c.classList.remove('audit-card-selected'));
          matchedCard.classList.add('audit-card-selected');
          matchedCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }
    }
  }

  function findRowForAction(act, rows) {
    if (!rows) rows = document.querySelectorAll('#rabTableBody tr.rab-row-item');
    let targetRow = null;
    const targetItemId = act.target_item_id || act.item_id || (act.changes && act.changes.item_id);

    // 1. Direct match by item ID (most accurate)
    if (targetItemId) {
      targetRow = document.querySelector(`#rabTableBody tr.rab-row-item[data-item-id="${targetItemId}"]`);
    }

    // 2. Direct match by item name
    const nameCandidates = [
      act.old_values?.item_name,
      act.changes?.item_name,
      act.item_name,
      act.target_item_name
    ].filter(Boolean);

    if (!targetRow && nameCandidates.length > 0) {
      for (const cand of nameCandidates) {
        const cLower = String(cand).toLowerCase().trim();
        for (const r of rows) {
          const nameSpan = r.querySelector('.rab-item-name');
          const rowName = (nameSpan ? nameSpan.textContent : (r.getAttribute('data-item-name') || '')).toLowerCase().trim();
          const rawName = (r.getAttribute('data-raw-name') || '').toLowerCase().trim();
          const ahspName = (r.getAttribute('data-ahsp-name') || '').toLowerCase().trim();
          if (rowName.includes(cLower) || cLower.includes(rowName) ||
              (rawName && (rawName.includes(cLower) || cLower.includes(rawName))) ||
              (ahspName && (ahspName.includes(cLower) || cLower.includes(ahspName)))) {
            targetRow = r;
            break;
          }
        }
        if (targetRow) break;
      }
    }

    // 3. Match from description text
    if (!targetRow && act.description) {
      const descLower = String(act.description).toLowerCase();
      for (const r of rows) {
        const nameSpan = r.querySelector('.rab-item-name');
        const rowName = (nameSpan ? nameSpan.textContent : (r.getAttribute('data-item-name') || '')).toLowerCase().trim();
        const rawName = (r.getAttribute('data-raw-name') || '').toLowerCase().trim();
        if ((rowName && rowName.length >= 4 && descLower.includes(rowName)) ||
            (rawName && rawName.length >= 4 && descLower.includes(rawName))) {
          targetRow = r;
          break;
        }
      }

      if (!targetRow) {
        const stopWords = ['mengubah', 'menjadi', 'untuk', 'dengan', 'harga', 'biaya', 'volume', 'satuan', 'pekerjaan', 'dari', 'pada', 'yang', 'dan', 'atau', 'tebal'];
        const words = descLower.replace(/[^\w\s]/g, ' ').split(/\s+/).filter(w => w.length >= 4 && !stopWords.includes(w));
        let bestScore = 0;
        for (const r of rows) {
          const nameSpan = r.querySelector('.rab-item-name');
          const rowName = (nameSpan ? nameSpan.textContent : (r.getAttribute('data-item-name') || '')).toLowerCase();
          const rawName = (r.getAttribute('data-raw-name') || '').toLowerCase();
          let score = 0;
          words.forEach(w => {
            if (rowName.includes(w)) score += 2;
            if (rawName.includes(w)) score += 2;
          });
          if (score > bestScore && score >= 2) {
            bestScore = score;
            targetRow = r;
          }
        }
      }
    }

    return targetRow;
  }

  async function applySingleActionToTableAndDb(act) {
    if (act.action_type === 'UPDATE_ITEM' && act.target_item_id) {
      const updatePayload = {};
      if (act.changes.item_name) updatePayload.item_name = act.changes.item_name;
      if (typeof act.changes.volume !== 'undefined') updatePayload.volume = parseFloat(act.changes.volume);
      if (act.changes.unit) updatePayload.unit = act.changes.unit;
      if (typeof act.changes.unit_price !== 'undefined') updatePayload.unit_price = parseFloat(act.changes.unit_price);
      if (act.changes.ahsp_code) {
        updatePayload.ahsp_code = act.changes.ahsp_code;
        updatePayload.ahsp_name = act.changes.item_name;
      }

      const res = await fetch(`<?= base_url("api/estimation-items") ?>/${act.target_item_id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(updatePayload)
      });

      if (!res.ok) throw new Error('Gagal update item');

      // Update table cells directly in DOM
      const tableRow = document.querySelector(`#rabTableBody tr[data-item-id="${act.target_item_id}"]`);
      if (tableRow) {
        const nameEl = tableRow.querySelector('.rab-item-name');
        if (nameEl && updatePayload.item_name) {
          nameEl.textContent = updatePayload.item_name;
          tableRow.dataset.itemName = updatePayload.item_name.toLowerCase();
        }
        if (updatePayload.ahsp_name) {
          tableRow.dataset.ahspName = updatePayload.ahsp_name.toLowerCase();
        }
        if (updatePayload.ahsp_code) {
          tableRow.dataset.ahspCode = updatePayload.ahsp_code.toLowerCase();
        }
        if (updatePayload.unit) {
          tableRow.dataset.itemUnit = updatePayload.unit.toLowerCase();
        }
        
        const cells = tableRow.querySelectorAll('td');
        if (cells.length >= 7) {
          if (typeof updatePayload.volume !== 'undefined') {
            cells[2].textContent = updatePayload.volume.toLocaleString('id-ID', { minimumFractionDigits: 2 });
          }
          if (updatePayload.unit) cells[3].textContent = updatePayload.unit;
          if (typeof updatePayload.unit_price !== 'undefined') {
            cells[4].textContent = 'Rp ' + updatePayload.unit_price.toLocaleString('id-ID', { minimumFractionDigits: 2 });
          }
          const currentVol = typeof updatePayload.volume !== 'undefined' 
            ? updatePayload.volume 
            : (parseFloat(cells[2].textContent.replace(/\./g, '').replace(',', '.')) || 0);
          const currentPrice = typeof updatePayload.unit_price !== 'undefined'
            ? updatePayload.unit_price
            : (parseFloat(cells[4].textContent.replace(/Rp|\s|\./g, '').replace(',', '.')) || 0);
          const newTotal = currentVol * currentPrice;
          cells[5].textContent = 'Rp ' + newTotal.toLocaleString('id-ID', { minimumFractionDigits: 2 });
        }

        // Flash highlight on the updated row
        tableRow.classList.remove('rab-row-flash-green');
        void tableRow.offsetWidth;
        tableRow.classList.add('rab-row-flash-green');
        setTimeout(() => tableRow.classList.remove('rab-row-flash-green'), 3000);
      }
      return true;
    } else if (act.action_type === 'ADD_ITEM') {
      const res = await fetch(`<?= base_url("api/estimation-items") ?>`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          section_id: act.target_section_id || 1,
          item_name: act.changes.item_name || act.description,
          volume: parseFloat(act.changes.volume) || 1,
          unit: act.changes.unit || 'm2',
          unit_price: parseFloat(act.changes.unit_price) || 0,
          ahsp_code: act.changes.ahsp_code || null,
          ahsp_status: 'unmapped'
        })
      });
      if (!res.ok) throw new Error('Gagal tambah item');
      return true;
    } else if (act.action_type === 'DELETE_ITEM' && act.target_item_id) {
      const res = await fetch(`<?= base_url("api/estimation-items") ?>/${act.target_item_id}`, {
        method: 'DELETE'
      });
      if (!res.ok) throw new Error('Gagal hapus item');
      const tableRow = document.querySelector(`#rabTableBody tr[data-item-id="${act.target_item_id}"]`);
      if (tableRow) {
        tableRow.style.opacity = '0.35';
        tableRow.style.textDecoration = 'line-through';
      }
      return true;
    }
  }

  async function persistProposalActionsState(historyId, actions) {
    if (!historyId) return;
    try {
      const projectId = `<?= esc($project['id']) ?>`;
      const allApplied = actions.every(a => a.is_applied);
      await fetch(`<?= base_url("api/projects") ?>/${projectId}/chat-history/${historyId}/applied`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          actions: actions,
          is_applied: allApplied ? 1 : 0
        })
      });
    } catch (err) {
      console.warn('Failed to persist proposal actions state:', err);
    }
  }

  function toggleProposalItemCheck(cardId, actIndex, isChecked) {
    const cardData = activeProposedActionsMap[cardId];
    if (!cardData || !cardData.actions || !cardData.actions[actIndex]) return;
    cardData.actions[actIndex].selected = isChecked;
    updateProposalCardState(cardId);
  }

  function renderProposalButtons(cardId) {
    const cardData = activeProposedActionsMap[cardId];
    if (!cardData || !cardData.actions) return;
    const btnRow = document.getElementById('buttons_' + cardId);
    if (!btnRow) return;

    const unapplied = cardData.actions.filter(a => !a.is_applied);
    const selected = unapplied.filter(a => a.selected !== false);

    if (unapplied.length === 0) {
      btnRow.innerHTML = `
        <button type="button" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow-sm" disabled style="background-color: #087f23; border-color: #087f23; font-size: 13px;">
          <i class="bi bi-check-circle-fill me-1.5"></i> Seluruh Perubahan Telah Diterapkan
        </button>
      `;
      return;
    }

    const label = selected.length === unapplied.length 
      ? `Terapkan Semua (${selected.length})` 
      : (selected.length > 0 ? `Terapkan Terpilih (${selected.length})` : 'Pilih Item');
    const disabled = selected.length === 0 ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : '';

    btnRow.innerHTML = `
      <button type="button" class="ai-btn-apply" id="btn_apply_${cardId}" onclick="executeApplyProposedActions('${cardId}')" ${disabled}>
        <i class="bi bi-check-lg" style="font-size: 15px;"></i>
        <span>${label}</span>
      </button>
      <button type="button" class="ai-btn-cancel" onclick="cancelProposedActions('${cardId}')">
        Batal
      </button>
    `;
  }

  function updateProposalCardState(cardId) {
    const cardData = activeProposedActionsMap[cardId];
    if (!cardData || !cardData.actions) return;

    const unapplied = cardData.actions.filter(a => !a.is_applied);
    const selected = unapplied.filter(a => a.selected !== false);

    // Update title count
    const titleCountEl = document.querySelector(`#${cardId} .ai-proposal-title span:last-child`);
    if (titleCountEl) {
      titleCountEl.textContent = `Usulan Perubahan (${unapplied.length})`;
    }

    // Update cost badge in header for selected unapplied items
    const headerCostBadge = document.querySelector(`#${cardId} .ai-proposal-cost-badge`);
    if (headerCostBadge) {
      const activeTotal = selected.reduce((acc, a) => acc + (parseFloat(a.cost_delta) || 0), 0);
      const sign = activeTotal > 0 ? '+' : (activeTotal < 0 ? '-' : '');
      headerCostBadge.textContent = `${sign} Rp ${Math.abs(Math.round(activeTotal)).toLocaleString('id-ID')}`;
      headerCostBadge.className = `ai-proposal-cost-badge ${activeTotal > 0 ? 'ai-proposal-cost-positive' : (activeTotal < 0 ? 'ai-proposal-cost-negative' : 'ai-proposal-cost-neutral')}`;
    }

    // Update each item visual state
    cardData.actions.forEach((act, idx) => {
      const itemEl = document.getElementById(`prop_item_${cardId}_${idx}`);
      if (itemEl && act.is_applied) {
        itemEl.classList.add('applied');
        const chk = itemEl.querySelector('.ai-item-checkbox');
        if (chk) chk.outerHTML = '<i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>';
        const accBtn = itemEl.querySelector('.ai-btn-acc-single');
        if (accBtn) accBtn.outerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 10px; font-weight: 600;">✓ Diterapkan</span>';
      }
    });

    renderProposalButtons(cardId);
  }

  async function executeApplySingleAction(cardId, actIndex, event) {
    if (event) event.stopPropagation();

    const cardData = activeProposedActionsMap[cardId];
    if (!cardData || !cardData.actions || !cardData.actions[actIndex]) return;
    const act = cardData.actions[actIndex];
    if (act.is_applied) return;

    const itemEl = document.getElementById(`prop_item_${cardId}_${actIndex}`);
    const applyBtn = itemEl ? itemEl.querySelector('.ai-btn-acc-single') : null;
    if (applyBtn) {
      applyBtn.disabled = true;
      applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 10px; height: 10px;"></span> Acc...';
    }

    try {
      await applySingleActionToTableAndDb(act);
      act.is_applied = true;
      act.selected = false;
      showToast('Sukses', `Perubahan item "${act.changes?.item_name || act.description || 'Pekerjaan'}" berhasil diterapkan!`, 'success');
      recalculateRabTotals();

      // Persist state to database
      await persistProposalActionsState(cardData.historyId, cardData.actions);

      // Check if all actions in card are now applied
      const unapplied = cardData.actions.filter(a => !a.is_applied);
      if (unapplied.length === 0) {
        const wrapper = document.getElementById('wrapper_' + cardId) || (document.getElementById(cardId) ? document.getElementById(cardId).closest('.ai-proposal-container') || document.getElementById(cardId).closest('.d-flex.mb-3') : null);
        if (wrapper) {
          wrapper.style.transition = 'all 0.35s ease';
          wrapper.style.opacity = '0';
          wrapper.style.transform = 'translateY(-6px)';
          setTimeout(() => { if (wrapper && wrapper.parentNode) wrapper.remove(); }, 350);
        }
        appendChatMessage('ai', `<div class="d-flex align-items-center gap-2 text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> Seluruh usulan perubahan telah diterapkan ke tabel RAB.</div>`);
      } else {
        updateProposalCardState(cardId);
      }
    } catch (err) {
      console.error('Error applying single action:', err);
      showToast('Gagal', 'Gagal menerapkan perubahan pada item ini.', 'error');
      if (applyBtn) {
        applyBtn.disabled = false;
        applyBtn.innerHTML = '<i class="bi bi-check2"></i> Acc';
      }
    }
  }

  function scrollToRabItemFromProposalAction(cardId, actIndex) {
    const cardData = activeProposedActionsMap[cardId];
    if (!cardData || !cardData.actions || !cardData.actions[actIndex]) return;
    const act = cardData.actions[actIndex];

    const rows = document.querySelectorAll('#rabTableBody tr.rab-row-item');
    if (!rows || rows.length === 0) return;

    // Reset previous flash highlights
    rows.forEach(r => {
      if (r._highlightTimer) clearTimeout(r._highlightTimer);
      r.classList.remove('rab-row-flash-red', 'rab-row-flash-amber', 'rab-row-flash-green');
    });

    const targetRow = findRowForAction(act, rows);

    // If target row was found, navigate & highlight like in audit rab
    if (targetRow) {
      // 1. Expand parent category section if it's collapsed (same mechanism as audit rab)
      const secCode = targetRow.getAttribute('data-sec-code');
      if (secCode) {
        const minusLine = document.getElementById('minus-line-' + secCode);
        const plusIcon = document.getElementById('plus-icon-' + secCode);
        const hiddenItems = document.querySelectorAll('.sec-items-' + secCode + '.d-none');
        if (hiddenItems.length > 0) {
          hiddenItems.forEach(el => el.classList.remove('d-none'));
          if (minusLine) minusLine.classList.remove('d-none');
          if (plusIcon) plusIcon.classList.add('d-none');
        }
      }

      // 2. Smoothly scroll into view centered
      targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

      // 3. Highlight / flash effect (same as audit rab: amber for ubah/warning)
      const isDelete = act.action_type === 'DELETE_ITEM';
      const isAdd = act.action_type === 'ADD_ITEM';
      const flashClass = isDelete ? 'rab-row-flash-red' : (isAdd ? 'rab-row-flash-green' : 'rab-row-flash-amber');
      
      targetRow.classList.add(flashClass);
      targetRow._highlightTimer = setTimeout(() => {
        targetRow.classList.remove(flashClass);
      }, 1500);
    } else if (act.action_type === 'ADD_ITEM' && act.target_section_id) {
      const secRow = document.querySelector(`#rabTableBody tr.rab-sec-row[data-sec-id="${act.target_section_id}"]`);
      if (secRow) {
        secRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        secRow.classList.add('rab-row-flash-green');
        setTimeout(() => secRow.classList.remove('rab-row-flash-green'), 1500);
      }
    }
  }

  function activateChatMode() {
    const centerArea = document.getElementById('aiInitialCenterArea');
    const headerBar = document.getElementById('aiActiveChatHeader');
    const historyLog = document.getElementById('aiChatHistoryLog');
    const bottomInput = document.getElementById('aiBottomInputContainer');

    if (centerArea) centerArea.classList.add('d-none');
    if (headerBar) {
      headerBar.classList.remove('d-none');
      headerBar.classList.add('d-flex');
    }
    if (historyLog) historyLog.classList.remove('d-none');
    if (bottomInput) bottomInput.classList.remove('d-none');
    focusAiChatInput();
  }

  function deactivateChatMode() {
    const centerArea = document.getElementById('aiInitialCenterArea');
    const headerBar = document.getElementById('aiActiveChatHeader');
    const historyLog = document.getElementById('aiChatHistoryLog');
    const bottomInput = document.getElementById('aiBottomInputContainer');

    if (centerArea) centerArea.classList.remove('d-none');
    if (headerBar) {
      headerBar.classList.add('d-none');
      headerBar.classList.remove('d-flex');
    }
    if (historyLog) {
      historyLog.classList.add('d-none');
      historyLog.innerHTML = '';
    }
    if (bottomInput) bottomInput.classList.add('d-none');
  }

  async function clearAiChatHistory() {
    if (!confirm('Apakah Anda yakin ingin membersihkan riwayat percakapan konsultasi ini?')) return;
    try {
      const projectId = `<?= esc($project['id']) ?>`;
      const res = await fetch(`<?= base_url("api/projects") ?>/${projectId}/chat-history`, {
        method: 'DELETE'
      });
      if (res.ok) {
        deactivateChatMode();
        showToast('Sukses', 'Riwayat konsultasi AI telah dibersihkan.', 'success');
      }
    } catch (e) {
      console.warn('Gagal menghapus riwayat chat:', e);
    }
  }

  async function sendAiPrompt(text) {
    activateChatMode();
    appendChatMessage('user', text);

    // Show loading indicator
    const stream = document.getElementById('aiChatHistoryLog');
    const loadingMsg = document.createElement('div');
    loadingMsg.className = 'd-flex mb-3 justify-content-start';
    loadingMsg.id = 'aiChatLoadingMsg';
    loadingMsg.innerHTML = `
      <div class="d-flex gap-2" style="max-width: 90%;">
        <div class="rounded-circle p-1.5 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background-color: #ecfdf5; color: #087f23; border: 1px solid #a7f3d0;">
          <i class="bi bi-stars" style="font-size: 12px;"></i>
        </div>
        <div class="px-3 py-2 rounded-3 shadow-sm text-muted" style="background-color: #f8fafc; border: 1px solid #e2e8f0; font-size: 12.5px;">
          <span class="spinner-border spinner-border-sm me-1" style="width: 12px; height: 12px;"></span> Memproses analisis AI...
        </div>
      </div>
    `;
    if (stream) {
      stream.appendChild(loadingMsg);
      stream.scrollTop = stream.scrollHeight;
    }

    const items = collectRABData();
    const projectId = `<?= esc($project['id']) ?>`;
    const payload = {
      project_id: projectId,
      prompt: text,
      project_context: {
        project_name: '<?= esc(addslashes($project['title'] ?? $project['name'] ?? 'Proyek')) ?>',
        building_type: '<?= esc(addslashes($project['building_type'] ?? 'Rumah Tinggal')) ?>',
        location: {
          province: '<?= esc(addslashes($project['province'] ?? 'Jawa Tengah')) ?>',
          city_regency: '<?= esc(addslashes($project['location'] ?? $project['city'] ?? 'Banyumas')) ?>',
        },
        building_area_m2: parseFloat('<?= esc($project['building_area'] ?? '100') ?>') || 100,
        number_of_floors: parseInt('<?= esc($project['floors'] ?? '1') ?>') || 1,
      },
      items: items,
      history: []
    };

    // Remove loading indicator helper
    const removeLoading = () => {
      const el = document.getElementById('aiChatLoadingMsg');
      if (el) el.remove();
    };

    // 1. Send request to AI RAB Agent (api/v2/ai/rab-agent)
    try {
      const res = await fetch(`<?= base_url("api/v2/ai/rab-agent") ?>`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });

      removeLoading();
      const json = await res.json().catch(() => ({}));

      if (res.ok) {
        const reply = json.reply_message || json.reply;
        if (reply) {
          let htmlReply = formatAiMarkdown(reply);
          appendChatMessage('ai', htmlReply);

          // Render separated Usulan Perubahan card if actions exist
          if (json.actions && json.actions.length > 0) {
            appendProposedActionsCard(json.actions, json.cost_impact, json.history_id, 0);
          }
          return;
        }
      }

      // If server returned an error, show the exact error message
      const errMsg = json.message || `HTTP ${res.status}: Gagal memproses permintaan ke AI Co-Pilot.`;
      appendChatMessage('ai', `<div class="text-danger py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Kendala AI Co-Pilot:</strong> ${escapeHtml(errMsg)}</div>`);
    } catch (e) {
      removeLoading();
      console.error('FastAPI AI RAB Agent connection error:', e);
      appendChatMessage('ai', `<div class="text-danger py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal Terhubung ke AI Co-Pilot:</strong> ${escapeHtml(e.message || 'Koneksi ke server AI terputus')}</div>`);
    }
  }

  async function sendAiChatMessageFromCenter() {
    const input = document.getElementById('aiChatInputCenter');
    const text = (input ? input.value : '').trim();
    if (!text) return;
    input.value = '';
    sendAiPrompt(text);
  }

  async function sendAiChatMessage() {
    const input = document.getElementById('aiChatInput');
    const text = (input ? input.value : '').trim();
    if (!text) return;
    input.value = '';
    sendAiPrompt(text);
  }

  function handleAiKeyPressCenter(e) {
    if (e.key === 'Enter') sendAiChatMessageFromCenter();
  }

  function handleAiKeyPress(e) {
    if (e.key === 'Enter') sendAiChatMessage();
  }

  function appendChatMessage(sender, text) {
    const log = document.getElementById('aiChatHistoryLog');
    if (!log) return;

    const msgDiv = document.createElement('div');
    msgDiv.className = `d-flex mb-3 ${sender === 'user' ? 'justify-content-end' : 'justify-content-start'}`;
    
    if (sender === 'user') {
      msgDiv.innerHTML = `
        <div class="px-3 py-2 rounded-3 text-white shadow-sm" style="background-color: #087f23; max-width: 85%; font-size: 13px;">
          ${escapeHtml(text)}
        </div>
      `;
    } else {
      msgDiv.innerHTML = `
        <div class="d-flex gap-2" style="max-width: 90%;">
          <div class="rounded-circle p-1.5 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; background-color: #ecfdf5; color: #087f23; border: 1px solid #a7f3d0;">
            <i class="bi bi-stars" style="font-size: 12px;"></i>
          </div>
          <div class="px-3 py-2.5 rounded-3 shadow-sm" style="background-color: #f8fafc; border: 1px solid #e2e8f0; font-size: 12.5px; line-height: 1.5; color: #1e293b;">
            ${text}
          </div>
        </div>
      `;
    }

    log.appendChild(msgDiv);
    log.scrollTop = log.scrollHeight;
  }

  // Active proposed actions map for user confirmation
  let activeProposedActionsMap = {};

  // Render Separated Usulan Perubahan Card
  function appendProposedActionsCard(actions, costImpact, historyId = null, isApplied = 0) {
    if (isApplied) return;
    const log = document.getElementById('aiChatHistoryLog');
    if (!log || !actions || actions.length === 0) return;

    // Check if all actions are already applied
    const unappliedAtStart = actions.filter(a => !a.is_applied);
    if (unappliedAtStart.length === 0) return;

    const cardId = 'aiPropCard_' + (historyId ? historyId : Date.now());
    activeProposedActionsMap[cardId] = {
      actions: actions,
      historyId: historyId
    };

    // Initialize selection state: only unapplied items are selected by default
    actions.forEach(act => {
      if (typeof act.selected === 'undefined') {
        act.selected = !act.is_applied;
      }
    });

    const activeToSum = actions.filter(a => !a.is_applied && a.selected !== false);
    const totalImpact = (typeof costImpact === 'number' && actions.every(a => !a.is_applied))
      ? costImpact
      : activeToSum.reduce((acc, a) => acc + (parseFloat(a.cost_delta) || 0), 0);
    
    const sign = totalImpact > 0 ? '+' : (totalImpact < 0 ? '-' : '');
    const absImpactFormatted = 'Rp ' + Math.abs(Math.round(totalImpact)).toLocaleString('id-ID');
    const badgeText = `${sign} ${absImpactFormatted}`;

    let badgeClass = 'ai-proposal-cost-neutral';
    if (totalImpact > 0) {
      badgeClass = 'ai-proposal-cost-positive';
    } else if (totalImpact < 0) {
      badgeClass = 'ai-proposal-cost-negative';
    }

    let itemsHtml = '';
    actions.forEach((act, idx) => {
      let badgeLabel = 'UBAH';
      let badgeCss = 'ai-badge-ubah';
      let itemModifierClass = 'item-ubah';
      if (act.action_type === 'ADD_ITEM') {
        badgeLabel = '+ TAMBAH';
        badgeCss = 'ai-badge-tambah';
        itemModifierClass = 'item-tambah';
      } else if (act.action_type === 'DELETE_ITEM') {
        badgeLabel = '- HAPUS';
        badgeCss = 'ai-badge-hapus';
        itemModifierClass = 'item-hapus';
      }

      const itemDelta = parseFloat(act.cost_delta) || 0;
      const itemSign = itemDelta > 0 ? '+' : (itemDelta < 0 ? '-' : '');
      const itemDeltaFormatted = itemDelta !== 0 
        ? `${itemSign} Rp ${Math.abs(Math.round(itemDelta)).toLocaleString('id-ID')}` 
        : '';
      const deltaColor = itemDelta > 0 ? '#dc2626' : (itemDelta < 0 ? '#15803d' : '#64748b');

      let subtext = '';
      if (act.changes && act.changes.unit_price) {
        subtext += `Harga Baru: Rp ${Math.round(act.changes.unit_price).toLocaleString('id-ID')} / ${act.changes.unit || 'm2'}`;
      }
      if (act.changes && act.changes.volume && act.changes.volume !== act.old_values?.volume) {
        subtext += (subtext ? ' &bull; ' : '') + `Volume: ${act.changes.volume} ${act.changes.unit || ''}`;
      }

      itemsHtml += `
        <div class="ai-proposal-item ${itemModifierClass} ${act.is_applied ? 'applied' : ''}" 
             id="prop_item_${cardId}_${idx}"
             onclick="scrollToRabItemFromProposalAction('${cardId}', ${idx})" 
             title="Klik untuk menuju ke baris data di tabel RAB">
          <div class="d-flex align-items-center justify-content-between mb-1.5 gap-2">
            <div class="d-flex align-items-center gap-2">
              ${!act.is_applied ? `
                <input type="checkbox" 
                       class="form-check-input ai-item-checkbox m-0" 
                       id="chk_${cardId}_${idx}" 
                       ${act.selected !== false ? 'checked' : ''} 
                       onclick="event.stopPropagation()" 
                       onchange="toggleProposalItemCheck('${cardId}', ${idx}, this.checked)"
                       title="Pilih item ini"
                >
              ` : `
                <i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>
              `}
              <span class="ai-proposal-item-badge ${badgeCss}">${badgeLabel}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span style="font-size: 12px; font-weight: 700; color: ${deltaColor};">${itemDeltaFormatted}</span>
              ${!act.is_applied ? `
                <button type="button" 
                        class="ai-btn-acc-single" 
                        onclick="executeApplySingleAction('${cardId}', ${idx}, event)"
                        title="Terapkan hanya item ini ke tabel RAB">
                  <i class="bi bi-check2"></i> Acc
                </button>
              ` : `
                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 10px; font-weight: 600;">✓ Diterapkan</span>
              `}
            </div>
          </div>
          <div style="font-size: 12.5px; font-weight: 600; color: #1e293b; line-height: 1.45;">
            ${escapeHtml(act.description || act.changes?.item_name || 'Penyesuaian spesifikasi pekerjaan')}
          </div>
          ${subtext ? `<div style="font-size: 11px; color: #64748b; margin-top: 3px;">${subtext}</div>` : ''}
        </div>
      `;
    });

    const unappliedCount = unappliedAtStart.length;
    const selectedCount = activeToSum.length;
    const label = selectedCount === unappliedCount 
      ? `Terapkan Semua (${selectedCount})` 
      : (selectedCount > 0 ? `Terapkan Terpilih (${selectedCount})` : 'Pilih Item');
    const disabled = selectedCount === 0 ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : '';

    const buttonsHtml = `
      <button type="button" class="ai-btn-apply" id="btn_apply_${cardId}" onclick="executeApplyProposedActions('${cardId}')" ${disabled}>
        <i class="bi bi-check-lg" style="font-size: 15px;"></i>
        <span>${label}</span>
      </button>
      <button type="button" class="ai-btn-cancel" onclick="cancelProposedActions('${cardId}')">
        Batal
      </button>
    `;

    const cardDiv = document.createElement('div');
    cardDiv.className = 'd-flex mb-3 justify-content-start ai-proposal-container';
    cardDiv.id = 'wrapper_' + cardId;
    cardDiv.style.width = '100%';
    cardDiv.innerHTML = `
      <div style="width: 100%; max-width: 96%; margin-left: auto; margin-right: auto;">
        <div class="ai-proposal-card" id="${cardId}">
          <div class="ai-proposal-header">
            <div class="ai-proposal-title">
              <span>📝</span>
              <span style="color: #087f23;">Usulan Perubahan (${unappliedCount})</span>
            </div>
            <div class="ai-proposal-cost-badge ${badgeClass}">
              ${badgeText}
            </div>
          </div>
          
          <div class="ai-proposal-body">
            ${itemsHtml}
          </div>

          <div class="ai-proposal-actions-row" id="buttons_${cardId}">
            ${buttonsHtml}
          </div>
        </div>
      </div>
    `;

    log.appendChild(cardDiv);
    log.scrollTop = log.scrollHeight;
  }

  // Recalculate RAB table totals in DOM without page reload
  function recalculateRabTotals() {
    let grandDirect = 0;

    const secRows = document.querySelectorAll('#rabTableBody tr.rab-sec-row');
    secRows.forEach(secRow => {
      const secCode = secRow.getAttribute('data-sec-code');
      const itemRows = document.querySelectorAll(`#rabTableBody tr.rab-row-item.sec-items-${secCode}`);
      let secTotal = 0;

      itemRows.forEach(row => {
        if (row.style.textDecoration === 'line-through') return;
        const cells = row.querySelectorAll('td');
        if (cells.length >= 6) {
          const rawPrice = cells[5].textContent.replace(/Rp|\s|\./g, '').replace(',', '.');
          const rowPrice = parseFloat(rawPrice) || 0;
          secTotal += rowPrice;
        }
      });

      grandDirect += secTotal;
      const subtotalEl = document.getElementById(`sec-subtotal-${secCode}`);
      if (subtotalEl) {
        subtotalEl.textContent = 'Rp ' + secTotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    });

    // Update section weights (bobot %)
    secRows.forEach(secRow => {
      const secCode = secRow.getAttribute('data-sec-code');
      const subtotalEl = document.getElementById(`sec-subtotal-${secCode}`);
      const bobotEl = document.getElementById(`sec-bobot-${secCode}`);
      if (subtotalEl && bobotEl && grandDirect > 0) {
        const secTotal = parseFloat(subtotalEl.textContent.replace(/Rp|\s|\./g, '').replace(',', '.')) || 0;
        const bobot = (secTotal / grandDirect) * 100;
        bobotEl.textContent = bobot.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
      }
    });

    const ppnRate = <?= (float) ($ppnRate ?? 0) ?>;
    const ppnAmount = grandDirect * (ppnRate / 100);
    const grandTotal = grandDirect + ppnAmount;

    const tfootJumlah = document.getElementById('tfootJumlahHarga');
    if (tfootJumlah) {
      tfootJumlah.textContent = 'Rp ' + grandDirect.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const tfootPpn = document.getElementById('tfootPpnAmount');
    if (tfootPpn) {
      tfootPpn.textContent = 'Rp ' + ppnAmount.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const tfootTotal = document.getElementById('tfootTotalHarga');
    if (tfootTotal) {
      tfootTotal.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  }

  // Execute Applying Proposed Changes into Database and Update Table
  async function executeApplyProposedActions(cardId) {
    const cardData = activeProposedActionsMap[cardId];
    if (!cardData || !cardData.actions) return;

    // Apply only items that are unapplied AND selected
    const toApply = cardData.actions.filter(a => !a.is_applied && a.selected !== false);
    if (toApply.length === 0) {
      showToast('Perhatian', 'Pilih minimal satu item perubahan untuk diterapkan.', 'warning');
      return;
    }

    const btnRow = document.getElementById('buttons_' + cardId);
    if (btnRow) {
      btnRow.innerHTML = `
        <button type="button" class="ai-btn-apply w-100" disabled style="opacity: 0.85; cursor: not-allowed;">
          <span class="spinner-border spinner-border-sm me-2" style="width: 13px; height: 13px;"></span>
          Menerapkan ${toApply.length} item ke tabel RAB...
        </button>
      `;
    }

    let successCount = 0;
    let failCount = 0;

    for (const act of toApply) {
      try {
        await applySingleActionToTableAndDb(act);
        act.is_applied = true;
        act.selected = false;
        successCount++;
      } catch (err) {
        console.error('Error applying action:', err);
        failCount++;
      }
    }

    recalculateRabTotals();

    // Persist state to database
    await persistProposalActionsState(cardData.historyId, cardData.actions);

    const remainingUnapplied = cardData.actions.filter(a => !a.is_applied);
    if (remainingUnapplied.length === 0) {
      showToast('Sukses', `${successCount} perubahan berhasil diterapkan ke tabel RAB!`, 'success');

      const wrapper = document.getElementById('wrapper_' + cardId) || (document.getElementById(cardId) ? document.getElementById(cardId).closest('.ai-proposal-container') || document.getElementById(cardId).closest('.d-flex.mb-3') : null);
      if (wrapper) {
        wrapper.style.transition = 'all 0.35s ease';
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateY(-6px)';
        setTimeout(() => { if (wrapper && wrapper.parentNode) wrapper.remove(); }, 350);
      }
      appendChatMessage('ai', `<div class="d-flex align-items-center gap-2 text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> Seluruh usulan perubahan (${successCount} item) telah berhasil diterapkan ke tabel RAB.</div>`);
    } else {
      showToast('Sukses', `${successCount} item berhasil diterapkan. Masih ada ${remainingUnapplied.length} item tersisa.`, 'info');
      updateProposalCardState(cardId);
    }
  }

  function cancelProposedActions(cardId) {
    const btnRow = document.getElementById('buttons_' + cardId);
    if (btnRow) {
      btnRow.innerHTML = `
        <div class="w-100 text-center py-1 text-muted" style="font-size: 12px; font-style: italic;">
          <i class="bi bi-x-circle me-1"></i> Usulan perubahan dibatalkan
        </div>
      `;
    }
    const card = document.getElementById(cardId);
    if (card) {
      card.style.opacity = '0.65';
    }
  }

  function formatAiMarkdown(text) {
    if (!text) return '';
    return text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\n\n/g, '<br><br>')
      .replace(/\n/g, '<br>');
  }

  function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }



  // Toggle Category Collapse/Expand
  function toggleCategoryCollapse(secCode) {
    const isCurrentlyCollapsed = collapsedSections[secCode] === true;
    collapsedSections[secCode] = !isCurrentlyCollapsed;

    const minusLine = document.getElementById(`minus-line-${secCode}`);
    const plusIcon = document.getElementById(`plus-icon-${secCode}`);
    const subRows = document.querySelectorAll(`.sec-items-${secCode}`);

    if (collapsedSections[secCode]) {
      if (minusLine) minusLine.classList.add('d-none');
      if (plusIcon) plusIcon.classList.remove('d-none');
      subRows.forEach(r => r.classList.add('d-none'));
    } else {
      if (minusLine) minusLine.classList.remove('d-none');
      if (plusIcon) plusIcon.classList.add('d-none');
      subRows.forEach(r => r.classList.remove('d-none'));
    }
  }

  function toggleAllCategoryCollapse() {
    const secRows = document.querySelectorAll('.rab-sec-row');
    if (secRows.length === 0) return;
    
    // Check if any section is currently expanded
    let anyExpanded = false;
    secRows.forEach(s => {
      const code = s.dataset.secCode;
      if (!collapsedSections[code]) anyExpanded = true;
    });

    secRows.forEach(s => {
      const code = s.dataset.secCode;
      collapsedSections[code] = anyExpanded;
      const minusLine = document.getElementById(`minus-line-${code}`);
      const plusIcon = document.getElementById(`plus-icon-${code}`);
      const subRows = document.querySelectorAll(`.sec-items-${code}`);

      if (anyExpanded) {
        if (minusLine) minusLine.classList.add('d-none');
        if (plusIcon) plusIcon.classList.remove('d-none');
        subRows.forEach(r => r.classList.add('d-none'));
      } else {
        if (minusLine) minusLine.classList.remove('d-none');
        if (plusIcon) plusIcon.classList.add('d-none');
        subRows.forEach(r => r.classList.remove('d-none'));
      }
    });
  }

  // Live Search Filter
  function handleRabSearch(val) {
    const rawVal = (val || '').trim();
    const rows = document.querySelectorAll('.rab-row-item');
    const secRows = document.querySelectorAll('.rab-sec-row');
    const existingNoResult = document.getElementById('rabSearchNoResult');

    if (!rawVal) {
      if (existingNoResult) existingNoResult.remove();
      rows.forEach(r => {
        const code = r.dataset.secCode;
        if (!collapsedSections[code]) {
          r.classList.remove('d-none');
        } else {
          r.classList.add('d-none');
        }
      });
      secRows.forEach(s => {
        s.classList.remove('d-none');
        const code = s.dataset.secCode;
        const minusLine = document.getElementById(`minus-line-${code}`);
        const plusIcon = document.getElementById(`plus-icon-${code}`);
        if (minusLine && plusIcon) {
          if (collapsedSections[code]) {
            minusLine.classList.add('d-none');
            plusIcon.classList.remove('d-none');
          } else {
            minusLine.classList.remove('d-none');
            plusIcon.classList.add('d-none');
          }
        }
      });
      return;
    }

    // Split keywords by space and remove empty elements
    const keywords = rawVal.toLowerCase().split(/\s+/).filter(Boolean);
    const matchingSecs = new Set();
    let totalMatchedItems = 0;

    rows.forEach(r => {
      // Collect all possible text representations of this row
      const name = (r.dataset.itemName || '').toLowerCase();
      const rawName = (r.dataset.rawName || '').toLowerCase();
      const ahspName = (r.dataset.ahspName || '').toLowerCase();
      const secName = (r.dataset.secName || '').toLowerCase();
      const ahspCode = (r.dataset.ahspCode || '').toLowerCase();
      const itemNo = (r.dataset.itemNo || '').toLowerCase();
      const unit = (r.dataset.itemUnit || '').toLowerCase();
      const textContent = (r.textContent || '').toLowerCase();

      const combined = `${name} ${rawName} ${ahspName} ${secName} ${ahspCode} ${itemNo} ${unit} ${textContent}`;
      const normalized = combined.replace(/[-_/\\().,]/g, ' ');
      const compacted = combined.replace(/[^a-z0-9]/g, '');

      // Every typed keyword token must be found in this row
      const isMatch = keywords.every(kw => {
        const kwClean = kw.replace(/[-_/\\().,]/g, '');
        return combined.includes(kw) || 
               normalized.includes(kw) || 
               (kwClean && compacted.includes(kwClean));
      });

      const secCode = r.dataset.secCode || '';

      if (isMatch) {
        r.classList.remove('d-none');
        matchingSecs.add(secCode);
        totalMatchedItems++;
      } else {
        r.classList.add('d-none');
      }
    });

    secRows.forEach(s => {
      const code = s.dataset.secCode;
      const secTitle = (s.dataset.secName || s.innerText || '').toLowerCase();
      const secDirectMatch = keywords.every(kw => secTitle.includes(kw));

      if (matchingSecs.has(code) || secDirectMatch) {
        s.classList.remove('d-none');
        // If category title matched directly, also reveal all its sub-items
        if (secDirectMatch) {
          const subRows = document.querySelectorAll(`.sec-items-${code}`);
          subRows.forEach(sub => {
            sub.classList.remove('d-none');
            totalMatchedItems++;
          });
        }
        // Expand the category toggle icon while showing search results
        const minusLine = document.getElementById(`minus-line-${code}`);
        const plusIcon = document.getElementById(`plus-icon-${code}`);
        if (minusLine && plusIcon) {
          minusLine.classList.remove('d-none');
          plusIcon.classList.add('d-none');
        }
      } else {
        s.classList.add('d-none');
      }
    });

    // Empty state message when nothing matches
    const tbody = document.getElementById('rabTableBody');
    if (totalMatchedItems === 0 && tbody) {
      if (!document.getElementById('rabSearchNoResult')) {
        const tr = document.createElement('tr');
        tr.id = 'rabSearchNoResult';
        tr.innerHTML = `
          <td colspan="8" class="text-center py-4 text-muted">
            <i class="bi bi-search fs-3 d-block mb-2 text-secondary opacity-50"></i>
            Tidak ada item pekerjaan yang cocok dengan pencarian "<strong>${escapeHtml(rawVal)}</strong>".
          </td>
        `;
        tbody.appendChild(tr);
      } else {
        document.getElementById('rabSearchNoResult').querySelector('strong').textContent = rawVal;
      }
    } else {
      const existingMsg = document.getElementById('rabSearchNoResult');
      if (existingMsg) existingMsg.remove();
    }
  }

  // Open Edit Item Modal
  function openEditItemModal(id, name, volume, unit, price) {
    document.getElementById('editRabItemId').value = id;
    document.getElementById('editRabItemName').textContent = name;
    document.getElementById('editRabVolumeInput').value = volume;
    document.getElementById('editRabUnitSpan').textContent = unit;
    document.getElementById('editRabPriceInput').value = price;
    editModalInstance.show();
  }

  async function saveRabItemEdit() {
    const id = document.getElementById('editRabItemId').value;
    const vol = parseFloat(document.getElementById('editRabVolumeInput').value) || 0;
    const price = parseFloat(document.getElementById('editRabPriceInput').value) || 0;

    try {
      const res = await fetch(`<?= base_url('api/estimation-items') ?>/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ volume: vol, unit_price: price })
      });

      if (res.ok) {
        showToast('Sukses', 'Data pekerjaan berhasil diperbarui.', 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast('Gagal', 'Gagal memperbarui pekerjaan.', 'error');
      }
    } catch (e) {
      showToast('Error', e.message || 'Terjadi kesalahan.', 'error');
    } finally {
      editModalInstance.hide();
    }
  }

  // Open Add Item Modal
  function openAddRabItemModal(secId, secCode) {
    document.getElementById('addRabSectionId').value = secId;
    document.getElementById('addRabItemNameInput').value = '';
    document.getElementById('addRabItemVolInput').value = '1.00';
    document.getElementById('addRabItemPriceInput').value = '0';
    addModalInstance.show();
  }

  async function executeAddRabItem() {
    const secId = document.getElementById('addRabSectionId').value;
    const name = document.getElementById('addRabItemNameInput').value.trim();
    const vol = parseFloat(document.getElementById('addRabItemVolInput').value) || 1;
    const unit = document.getElementById('addRabItemUnitInput').value.trim() || 'm2';
    const price = parseFloat(document.getElementById('addRabItemPriceInput').value) || 0;

    if (!name) {
      showToast('Peringatan', 'Uraian pekerjaan tidak boleh kosong.', 'warning');
      return;
    }

    try {
      const res = await fetch(`<?= base_url('api/estimation-items') ?>`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          section_id: secId,
          item_name: name,
          volume: vol,
          unit: unit,
          unit_price: price,
          ahsp_status: 'unmapped'
        })
      });

      if (res.ok) {
        showToast('Sukses', 'Pekerjaan baru berhasil ditambahkan.', 'success');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast('Gagal', 'Gagal menambahkan pekerjaan.', 'error');
      }
    } catch (e) {
      showToast('Error', e.message || 'Terjadi kesalahan.', 'error');
    } finally {
      addModalInstance.hide();
    }
  }

  // Confirm Delete Item / Section
  function confirmDeleteItem(id, name) {
    document.getElementById('deleteRabTargetId').value = id;
    document.getElementById('deleteRabTargetType').value = 'item';
    document.getElementById('deleteRabModalTitle').textContent = 'Hapus Pekerjaan Ini?';
    document.getElementById('deleteRabModalDesc').textContent = `Apakah Anda yakin ingin menghapus "${name}" dari tabel RAB?`;
    deleteModalInstance.show();
  }

  function confirmDeleteSection(id, name) {
    document.getElementById('deleteRabTargetId').value = id;
    document.getElementById('deleteRabTargetType').value = 'section';
    document.getElementById('deleteRabModalTitle').textContent = 'Hapus Kategori Ini?';
    document.getElementById('deleteRabModalDesc').textContent = `Apakah Anda yakin ingin menghapus seluruh kategori "${name}" beserta pekerjaan di dalamnya?`;
    deleteModalInstance.show();
  }

  async function executeDeleteRab() {
    const id = document.getElementById('deleteRabTargetId').value;
    const type = document.getElementById('deleteRabTargetType').value;

    try {
      const endpoint = type === 'item' 
        ? `<?= base_url('api/estimation-items') ?>/${id}`
        : `<?= base_url('api/wbs-sections') ?>/${id}`;

      const res = await fetch(endpoint, { method: 'DELETE' });

      if (res.ok) {
        showToast('Terhapus', 'Data berhasil dihapus dari RAB.', 'warning');
        setTimeout(() => location.reload(), 500);
      } else {
        showToast('Gagal', 'Gagal menghapus data.', 'error');
      }
    } catch (e) {
      showToast('Error', e.message || 'Terjadi kesalahan.', 'error');
    } finally {
      deleteModalInstance.hide();
    }
  }
</script>

<?= $this->endSection() ?>
