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

  /* ------------------------------------------------------------- */
  /* TABLE DESIGN (1:1 DENGAN SCREENSHOT REFERENSI)               */
  /* ------------------------------------------------------------- */
  .rab-table-wrapper {
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 80vh;
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

  /* Badge Unmapped */
  .badge-unmapped-mini {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    background-color: #fee2e2;
    border: 1px solid #f87171;
    color: #dc2626;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 800;
    margin-left: 6px;
    vertical-align: middle;
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
    .app-navbar, .rab-search-box, .no-print, .btn-item-icon, .btn-sec-action, .btn-toggle-sec,
    .ai-fab-btn, .ai-audit-drawer, .ai-audit-overlay {
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

  /* ------------------------------------------------------------- */
  /* AI RAB AUDITOR: FAB BUTTON                                    */
  /* ------------------------------------------------------------- */
  .ai-fab-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 1050;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #087f23, #0a9c2d);
    color: #ffffff;
    border: none;
    border-radius: 50px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(8, 127, 35, 0.35), 0 2px 6px rgba(0,0,0,0.12);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    letter-spacing: 0.02em;
  }
  .ai-fab-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(8, 127, 35, 0.45), 0 4px 10px rgba(0,0,0,0.15);
    background: linear-gradient(135deg, #0a9c2d, #0cba35);
  }
  .ai-fab-btn:active {
    transform: translateY(0);
  }
  .ai-fab-btn .fab-icon {
    font-size: 18px;
    line-height: 1;
  }
  .ai-fab-badge {
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 4px;
    display: none;
  }

  /* ------------------------------------------------------------- */
  /* AI RAB AUDITOR: SLIDE-OVER DRAWER                             */
  /* ------------------------------------------------------------- */
  .ai-audit-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.15);
    z-index: 1060;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
  }
  .ai-audit-overlay.active {
    opacity: 1;
    pointer-events: auto;
  }

  .ai-audit-drawer {
    position: fixed;
    top: 0;
    right: -460px;
    width: 440px;
    max-width: 95vw;
    height: 100vh;
    background: #ffffff;
    z-index: 1070;
    box-shadow: -4px 0 30px rgba(0, 0, 0, 0.12);
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .ai-audit-drawer.open {
    right: 0;
  }

  .ai-drawer-header {
    background: linear-gradient(135deg, #087f23, #0a9c2d);
    color: #ffffff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
  }
  .ai-drawer-header h5 {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .ai-drawer-close {
    background: none;
    border: none;
    color: #ffffff;
    font-size: 20px;
    cursor: pointer;
    padding: 4px;
    line-height: 1;
    opacity: 0.8;
    transition: opacity 0.15s;
  }
  .ai-drawer-close:hover { opacity: 1; }

  .ai-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
  }
  .ai-drawer-body::-webkit-scrollbar { width: 6px; }
  .ai-drawer-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

  /* Health Score Gauge */
  .ai-score-container {
    text-align: center;
    padding: 16px 0 20px;
  }
  .ai-score-ring {
    width: 120px;
    height: 120px;
    margin: 0 auto 12px;
    position: relative;
  }
  .ai-score-ring svg {
    width: 120px;
    height: 120px;
    transform: rotate(-90deg);
  }
  .ai-score-ring .ring-bg {
    fill: none;
    stroke: #e2e8f0;
    stroke-width: 8;
  }
  .ai-score-ring .ring-fill {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1), stroke 0.5s;
  }
  .ai-score-number {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
  .ai-score-number .score-val {
    font-size: 32px;
    font-weight: 800;
    line-height: 1;
  }
  .ai-score-number .score-max {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
  }
  .ai-score-status {
    font-size: 13px;
    font-weight: 600;
    margin-top: 4px;
  }

  /* Summary Pills */
  .ai-summary-row {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
  }
  .ai-summary-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
  }
  .pill-critical { background: #fef2f2; color: #dc2626; }
  .pill-warning  { background: #fffbeb; color: #d97706; }
  .pill-missing  { background: #fefce8; color: #ca8a04; }
  .pill-ok       { background: #f0fdf4; color: #16a34a; }

  /* Anomaly Cards */
  .ai-finding-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #ffffff;
  }
  .ai-finding-card:hover {
    border-color: #087f23;
    box-shadow: 0 2px 8px rgba(8, 127, 35, 0.1);
    transform: translateX(-2px);
  }
  .ai-finding-card .card-severity {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
  }
  .ai-finding-card .card-severity.critical { color: #dc2626; }
  .ai-finding-card .card-severity.warning  { color: #d97706; }
  .ai-finding-card .card-severity.missing  { color: #ca8a04; }
  .ai-finding-card .card-message {
    font-size: 12.5px;
    color: #334155;
    margin-bottom: 6px;
    line-height: 1.5;
  }
  .ai-finding-card .card-recommend {
    font-size: 11.5px;
    color: #64748b;
    line-height: 1.4;
    padding-left: 10px;
    border-left: 2px solid #e2e8f0;
  }

  .ai-drawer-footer {
    border-top: 1px solid #e2e8f0;
    padding: 12px 20px;
    display: flex;
    gap: 10px;
    flex-shrink: 0;
    background: #f8fafc;
  }
  .ai-drawer-footer button {
    flex: 1;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
  }
  .btn-reaudit {
    background: linear-gradient(135deg, #087f23, #0a9c2d);
    color: #ffffff;
  }
  .btn-reaudit:hover {
    box-shadow: 0 2px 8px rgba(8, 127, 35, 0.3);
  }

  /* Loading Spinner */
  .ai-loading {
    text-align: center;
    padding: 60px 20px;
  }
  .ai-loading .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #087f23;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .ai-loading p {
    font-size: 13px;
    color: #64748b;
  }

  /* Table Row Highlights */
  .rab-row-item.audit-critical {
    background-color: #fef2f2 !important;
  }
  .rab-row-item.audit-warning {
    background-color: #fffbeb !important;
  }
  .audit-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    font-size: 10px;
    margin-left: 4px;
    cursor: help;
    position: relative;
  }
  .audit-badge.badge-critical {
    background: #fecaca;
    color: #dc2626;
  }
  .audit-badge.badge-warning {
    background: #fef3c7;
    color: #d97706;
  }

  /* ------------------------------------------------------------- */
  /* AI CO-PILOT EDITOR STYLES                                     */
  /* ------------------------------------------------------------- */
  .ai-tab-nav {
    display: flex;
    background: #f1f5f9;
    padding: 6px 12px;
    gap: 6px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
  }
  .ai-tab-btn {
    flex: 1;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
  .ai-tab-btn.active {
    background: #ffffff;
    color: #087f23;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
  }
  .ai-pane {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
    height: 100%;
  }
  .ai-copilot-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .ai-copilot-body::-webkit-scrollbar { width: 6px; }
  .ai-copilot-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
  
  .ai-welcome-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
  }
  .ai-chip-section {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .ai-chip-title {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .ai-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .ai-chip {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    padding: 5px 11px;
    font-size: 11px;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s;
    text-align: left;
  }
  .ai-chip:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
    color: #087f23;
  }
  .ai-chat-stream {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .ai-msg {
    max-width: 90%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 12px;
    line-height: 1.5;
    word-break: break-word;
  }
  .ai-msg-user {
    align-self: flex-end;
    background: #087f23;
    color: #ffffff;
    border-bottom-right-radius: 2px;
  }
  .ai-msg-assistant {
    align-self: flex-start;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #1e293b;
    border-bottom-left-radius: 2px;
  }
  .ai-copilot-footer {
    padding: 12px 16px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
  }
  .ai-input-field {
    flex: 1;
    padding: 8px 12px;
    font-size: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    resize: none;
    outline: none;
    line-height: 1.4;
    transition: border-color 0.15s;
  }
  .ai-input-field:focus {
    border-color: #087f23;
    box-shadow: 0 0 0 2px rgba(8, 127, 35, 0.12);
  }
  .ai-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: #087f23;
    color: #ffffff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
    flex-shrink: 0;
  }
  .ai-send-btn:hover {
    background: #06651c;
  }

  /* Proposal Card & Action Badges */
  .ai-proposal-card {
    background: #ffffff;
    border: 1.5px solid #087f23;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 4px 14px rgba(8, 127, 35, 0.08);
  }
  .ai-proposal-header {
    font-size: 12px;
    font-weight: 700;
    color: #087f23;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
  }
  .ai-action-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    margin-bottom: 6px;
    font-size: 11.5px;
    cursor: pointer;
    transition: border-color 0.15s;
  }
  .ai-action-item:hover {
    border-color: #087f23;
    background: #f0fdf4;
  }
  .ai-badge-act {
    font-size: 9.5px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    margin-right: 4px;
  }
  .ai-badge-act.badge-update { background: #fef3c7; color: #b45309; }
  .ai-badge-act.badge-add { background: #dcfce7; color: #15803d; }
  .ai-badge-act.badge-delete { background: #fee2e2; color: #b91c1c; }

  .ai-cost-impact-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11.5px;
  }
  .ai-cost-impact-pill.cost-up { background: #fee2e2; color: #dc2626; }
  .ai-cost-impact-pill.cost-down { background: #dcfce7; color: #16a34a; }
  .ai-cost-impact-pill.cost-neutral { background: #f1f5f9; color: #475569; }

  /* Target Highlight in Main Table */
  @keyframes aiPulseRow {
    0% { background-color: rgba(8, 127, 35, 0.22); }
    50% { background-color: rgba(8, 127, 35, 0.06); }
    100% { background-color: rgba(8, 127, 35, 0.22); }
  }
  .rab-row-item.ai-target-highlight {
    animation: aiPulseRow 1.8s infinite ease-in-out !important;
    outline: 2px solid #087f23 !important;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Banner (Identik dengan Desain Anggaran) -->
<div class="anggaran-banner">
  <img src="<?= base_url('assets/img/proyek-bg13.png') ?>" alt="Banner Estimator" class="anggaran-banner-bg" onerror="this.style.display='none'">
  <h1 class="anggaran-banner-title"><?= esc($project['title']) ?></h1>
</div>

<!-- Main Workspace Container (Sama persis lebar & paddingnya dengan Anggaran: container-fluid max-width 1440px) -->
<main class="container-fluid px-3 px-md-5 my-4" style="max-width: 1440px;">
  <div class="anggaran-card">

    <!-- Top Control: Search Filter -->
    <div class="d-flex align-items-center justify-content-end mb-3 no-print">
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
            <th scope="col" class="text-center" style="width: 120px;">Kode AHSP</th>
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
              <td colspan="9" class="py-5 text-center text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada data pekerjaan untuk proyek ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sections as $sIdx => $sec): ?>
              <!-- Category / Section Header Row -->
              <tr class="rab-sec-row" data-sec-code="<?= esc($sec['code']) ?>" data-sec-id="<?= esc($sec['id']) ?>">
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

                <!-- Col 3: Kode AHSP (Blank) -->
                <td></td>

                <!-- Col 4: Volume (Blank) -->
                <td></td>

                <!-- Col 5: Satuan (Blank) -->
                <td></td>

                <!-- Col 6: Harga Satuan (Blank) -->
                <td></td>

                <!-- Col 7: Harga (Section Subtotal) -->
                <td class="text-end fw-medium text-dark tab-num" style="font-size: 12px;" id="sec-subtotal-<?= esc($sec['code']) ?>">
                  Rp <?= number_format($sec['subtotal'], 2, ',', '.') ?>
                </td>

                <!-- Col 8: % (Section Bobot) -->
                <td class="text-end fw-medium text-dark tab-num" style="font-size: 12px;" id="sec-bobot-<?= esc($sec['code']) ?>">
                  <?= number_format($sec['bobot'], 2, ',', '.') ?> %
                </td>

                <!-- Col 9: Aksi (Green + and Trash buttons) -->
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
                <tr 
                  class="rab-row-item sec-items-<?= esc($sec['code']) ?>" 
                  data-sec-code="<?= esc($sec['code']) ?>"
                  data-item-id="<?= esc($it['id']) ?>"
                  data-item-name="<?= esc(strtolower($it['name'])) ?>"
                  data-ahsp-code="<?= esc(strtolower($it['ahsp_code'] ?? '')) ?>"
                >
                  <!-- Col 1: Item No. -->
                  <td class="text-center text-muted fw-semibold tab-num" style="font-size: 11.5px;">
                    <?= esc($it['no']) ?>
                  </td>

                  <!-- Col 2: Uraian Pekerjaan & Warning Badge if Unmapped -->
                  <td style="padding-left: 32px;">
                    <div class="d-flex align-items-center gap-1.5">
                      <span class="rab-item-name">
                        <?= esc($it['ahsp_name'] ?: $it['name']) ?>
                      </span>
                      <?php if (($it['ahsp_status'] ?? '') === 'unmapped'): ?>
                        <span class="badge-unmapped-mini" title="AHSP belum dipetakan">!</span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Col 3: Kode AHSP -->
                  <td class="text-center text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= esc($it['ahsp_code'] ?: '-') ?>
                  </td>

                  <!-- Col 4: Volume -->
                  <td class="text-center text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= number_format($it['volume'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 5: Satuan -->
                  <td class="text-center text-muted" style="font-size: 12px;">
                    <?= esc($it['unit']) ?>
                  </td>

                  <!-- Col 6: Harga Satuan -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    Rp <?= number_format($it['unit_price'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 7: Harga (Total) -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    Rp <?= number_format($it['subtotal'], 2, ',', '.') ?>
                  </td>

                  <!-- Col 8: % (Bobot) -->
                  <td class="text-end text-dark fw-medium tab-num" style="font-size: 12px;">
                    <?= number_format($it['bobot'], 2, ',', '.') ?> %
                  </td>

                  <!-- Col 9: Aksi (Pencil, Book, Trash) -->
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                      <!-- Edit Item -->
                      <button 
                        type="button" 
                        class="btn-item-icon edit-icon" 
                        onclick="openEditItemModal('<?= esc($it['id']) ?>', '<?= esc(addslashes($it['name'])) ?>', <?= (float)$it['volume'] ?>, '<?= esc($it['unit']) ?>', <?= (float)$it['unit_price'] ?>)" 
                        title="Ubah Item"
                      >
                        <i class="bi bi-pencil-square" style="font-size: 13.5px;"></i>
                      </button>

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

        <!-- Tfoot: Solid Dark Green Summary (Matching Screenshot) -->
        <tfoot>
          <!-- Row 1: JUMLAH HARGA -->
          <tr>
            <td colspan="6" class="text-end text-uppercase fw-bold text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              JUMLAH HARGA
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" id="tfootJumlahHarga" style="background-color: #087f23 !important;">
              Rp <?= number_format($grandTotal, 2, ',', '.') ?>
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" style="background-color: #087f23 !important;">
              <?= $grandTotal > 0 ? '100.00 %' : '0.00 %' ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>

          <!-- Row 2: PPN 0.00 % -->
          <tr>
            <td colspan="6" class="text-end text-uppercase fw-bold text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              PPN <?= number_format($ppnRate, 2, '.', '') ?> %
            </td>
            <td class="text-end fw-bold text-white tab-num pe-3" id="tfootPpnAmount" style="background-color: #087f23 !important;">
              Rp <?= number_format($grandTotal * ($ppnRate / 100), 2, ',', '.') ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>

          <!-- Row 3: TOTAL HARGA -->
          <tr class="total-row">
            <td colspan="6" class="text-end text-uppercase fw-bolder text-white pe-3" style="letter-spacing: 0.04em; background-color: #087f23 !important;">
              TOTAL HARGA
            </td>
            <td class="text-end fw-bolder text-white tab-num pe-3" id="tfootTotalHarga" style="background-color: #087f23 !important;">
              Rp <?= number_format($grandTotal * (1 + ($ppnRate / 100)), 2, ',', '.') ?>
            </td>
            <td style="background-color: #087f23 !important;"></td>
            <td style="background-color: #087f23 !important;"></td>
          </tr>
        </tfoot>
      </table>
    </div>

  </div>
</main>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- AI RAB AUDITOR: FAB BUTTON                                        -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<button type="button" class="ai-fab-btn no-print" id="aiFabBtn" onclick="openAuditDrawer()" title="Audit RAB dengan AI">
  <span class="fab-icon">✨</span>
  <span>Asisten AI</span>
  <span class="ai-fab-badge" id="aiFabBadge">0</span>
</button>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- AI RAB AUDITOR: SLIDE-OVER DRAWER                                 -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="ai-audit-overlay" id="aiAuditOverlay" onclick="closeAuditDrawer()"></div>
<div class="ai-audit-drawer" id="aiAuditDrawer">
  <!-- Header -->
  <div class="ai-drawer-header">
    <h5><span>✨</span> Asisten AI Estimator</h5>
    <button class="ai-drawer-close" onclick="closeAuditDrawer()">&times;</button>
  </div>

  <!-- Dual Mode Tabs -->
  <div class="ai-tab-nav">
    <button type="button" class="ai-tab-btn active" id="tabBtnCopilot" onclick="switchAiTab('copilot')">
      💬 Co-Pilot Editor
    </button>
    <button type="button" class="ai-tab-btn" id="tabBtnAudit" onclick="switchAiTab('audit')">
      🔍 Audit Kelayakan
    </button>
  </div>

  <!-- TAB 1: CO-PILOT EDITOR PANE -->
  <div class="ai-pane" id="paneCopilot">
    <div class="ai-copilot-body" id="aiCopilotBody">
      <!-- Welcome Box -->
      <div class="ai-welcome-box">
        <div style="font-size: 26px; margin-bottom: 6px;">🤖</div>
        <div style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 2px;">AI Co-Estimator Siap Membantu</div>
        <div style="font-size: 11.5px; color: #64748b; line-height: 1.5;">
          Perintahkan untuk ganti material, tambah pekerjaan, naikkan volume, atau pangkas biaya RAB.
        </div>
      </div>

      <!-- Quick Suggestion Chips -->
      <div class="ai-chip-section">
        <div class="ai-chip-title">Perintah Cepat:</div>
        <div class="ai-chip-list">
          <button type="button" class="ai-chip" onclick="applyQuickPrompt('Ganti semua pekerjaan lantai keramik 40x40 menjadi granit tile 60x60')">
            🔄 Ganti keramik ke granit 60x60
          </button>
          <button type="button" class="ai-chip" onclick="applyQuickPrompt('Tambahkan 12 titik stop kontak dan 6 saklar ganda di bagian MEP')">
            ➕ Tambah instalasi listrik (MEP)
          </button>
          <button type="button" class="ai-chip" onclick="applyQuickPrompt('Tambahkan faktor waste 10% untuk pekerjaan plesteran dan acian')">
            📐 Tambah waste 10% plesteran
          </button>
          <button type="button" class="ai-chip" onclick="applyQuickPrompt('Pangkas biaya finishing sebesar 10% dengan material alternatif')">
            💰 Optimasi biaya finishing
          </button>
        </div>
      </div>

      <!-- Chat Stream -->
      <div class="ai-chat-stream" id="aiChatStream"></div>

      <!-- Proposed Actions Container -->
      <div id="aiProposedActionsContainer" style="display: none;"></div>
    </div>

    <!-- Copilot Input Box -->
    <div class="ai-copilot-footer">
      <form id="aiCopilotForm" onsubmit="submitCopilotPrompt(event)" class="d-flex gap-2 align-items-center">
        <textarea
          id="aiCopilotInput"
          class="ai-input-field"
          rows="1"
          placeholder="Ketik instruksi... (Enter untuk kirim)"
          onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); submitCopilotPrompt(event);}"
        ></textarea>
        <button type="submit" class="ai-send-btn" id="aiSendBtn" title="Kirim instruksi">
          <span id="aiSendIcon">➤</span>
          <span id="aiSendSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
        </button>
      </form>
      <div style="font-size: 10px; color: #94a3b8; margin-top: 6px; text-align: center;">
        Perubahan akan ditampilkan sebagai preview sebelum diterapkan ke tabel.
      </div>
    </div>
  </div>

  <!-- TAB 2: AUDIT KELAYAKAN PANE -->
  <div class="ai-pane d-none" id="paneAudit">
    <div class="ai-drawer-body" id="aiDrawerBody">
      <!-- Initial state: prompt to run audit -->
      <div id="aiAuditInitial" class="text-center" style="padding: 60px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
        <h6 style="font-weight: 700; color: #334155; margin-bottom: 8px;">Audit Kelayakan RAB</h6>
        <p style="font-size: 12.5px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">
          Periksa kelayakan volume, harga satuan, dan kelengkapan item pekerjaan secara otomatis menggunakan AI.
        </p>
        <button type="button" class="btn-reaudit" style="padding: 10px 24px; border-radius: 10px; font-size: 13px;" onclick="runAudit()">
          🚀 Jalankan Audit Sekarang
        </button>
      </div>

      <!-- Loading state -->
      <div id="aiAuditLoading" class="ai-loading" style="display: none;">
        <div class="spinner"></div>
        <p>Menganalisis RAB dengan AI...</p>
        <p style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Memeriksa volume, harga, dan kelengkapan pekerjaan</p>
      </div>

      <!-- Results -->
      <div id="aiAuditResults" style="display: none;"></div>
    </div>

    <!-- Footer -->
    <div class="ai-drawer-footer" id="aiDrawerFooter" style="display: none;">
      <button type="button" class="btn-reaudit" onclick="runAudit()">
        🔄 Jalankan Ulang Audit
      </button>
    </div>
  </div>
</div>

<!-- Modal: Ubah Item (Volume & Harga Satuan) -->
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
  });

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

  // Live Search Filter
  function handleRabSearch(val) {
    const term = (val || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.rab-row-item');
    const secRows = document.querySelectorAll('.rab-sec-row');

    if (!term) {
      rows.forEach(r => {
        const code = r.dataset.secCode;
        if (!collapsedSections[code]) {
          r.classList.remove('d-none');
        }
      });
      secRows.forEach(s => s.classList.remove('d-none'));
      return;
    }

    const matchingSecs = new Set();

    rows.forEach(r => {
      const name = r.dataset.itemName || '';
      const ahspCode = r.dataset.ahspCode || '';
      const secCode = r.dataset.secCode || '';

      if (name.includes(term) || ahspCode.includes(term)) {
        r.classList.remove('d-none');
        matchingSecs.add(secCode);
      } else {
        r.classList.add('d-none');
      }
    });

    secRows.forEach(s => {
      const code = s.dataset.secCode;
      if (matchingSecs.has(code) || (s.innerText || '').toLowerCase().includes(term)) {
        s.classList.remove('d-none');
      } else {
        s.classList.add('d-none');
      }
    });
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

  // ═══════════════════════════════════════════════════════════════════
  // ═══════════════════════════════════════════════════════════════════
  // AI ASSISTANT & CO-PILOT — Frontend Logic
  // ═══════════════════════════════════════════════════════════════════

  const AI_AUDIT_API = 'http://127.0.0.1:8200/api/v2/ai/rab-audit';
  const AI_AGENT_API = 'http://127.0.0.1:8200/api/v2/ai/rab-agent';

  let lastAuditResult = null;
  let copilotChatHistory = [];
  let currentProposedActions = [];

  function openAuditDrawer(tab = 'copilot') {
    document.getElementById('aiAuditOverlay').classList.add('active');
    document.getElementById('aiAuditDrawer').classList.add('open');
    if (tab) switchAiTab(tab);
  }

  function closeAuditDrawer() {
    document.getElementById('aiAuditOverlay').classList.remove('active');
    document.getElementById('aiAuditDrawer').classList.remove('open');
    clearTargetHighlights();
  }

  function switchAiTab(tab) {
    const btnCopilot = document.getElementById('tabBtnCopilot');
    const btnAudit = document.getElementById('tabBtnAudit');
    const paneCopilot = document.getElementById('paneCopilot');
    const paneAudit = document.getElementById('paneAudit');

    if (tab === 'copilot') {
      btnCopilot.classList.add('active');
      btnAudit.classList.remove('active');
      paneCopilot.classList.remove('d-none');
      paneAudit.classList.add('d-none');
    } else {
      btnAudit.classList.add('active');
      btnCopilot.classList.remove('active');
      paneAudit.classList.remove('d-none');
      paneCopilot.classList.add('d-none');
    }
  }

  function clearTargetHighlights() {
    document.querySelectorAll('.ai-target-highlight').forEach(el => {
      el.classList.remove('ai-target-highlight');
    });
  }

  function highlightItemRow(itemId) {
    clearTargetHighlights();
    const row = document.querySelector(`tr.rab-row-item[data-item-id="${itemId}"]`);
    if (row) {
      row.classList.add('ai-target-highlight');
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function applyQuickPrompt(text) {
    const input = document.getElementById('aiCopilotInput');
    if (!input) return;
    input.value = text;
    input.focus();
    submitCopilotPrompt();
  }

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
      const description = nameEl ? nameEl.textContent.trim() : '';
      const ahspCode = (cells[2]?.textContent?.trim() || '').replace('-', '');
      const volumeText = cells[3]?.textContent?.trim().replace(/\./g, '').replace(',', '.') || '0';
      const unit = cells[4]?.textContent?.trim() || '';
      const priceText = cells[5]?.textContent?.trim().replace('Rp', '').replace(/\./g, '').replace(',', '.').trim() || '0';
      const totalText = cells[6]?.textContent?.trim().replace('Rp', '').replace(/\./g, '').replace(',', '.').trim() || '0';

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

  // ─────────────────────────────────────────────────────────────────
  // CO-PILOT SUBMIT & PROPOSAL HANDLERS
  // ─────────────────────────────────────────────────────────────────

  function formatAiMarkdown(text) {
    if (!text) return '';
    let safe = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    // Bold: **text** -> <strong>text</strong>
    safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Italic: *text* -> <em>text</em>
    safe = safe.replace(/\*([^\*\n]+)\*/g, '<em>$1</em>');

    // Ordered list: 1. Item
    safe = safe.replace(/(?:^|\n)(\d+)\.\s+([^\n]+)/g, '<div class="mt-2 mb-1 fw-semibold text-dark"><span class="badge bg-success-subtle text-success border border-success-subtle me-1.5" style="font-size: 10.5px;">$1</span> $2</div>');

    // Bullet list: - Item
    safe = safe.replace(/(?:^|\n)[-\*]\s+([^\n]+)/g, '<div class="ms-2.5 my-1 text-secondary" style="line-height: 1.5;"><span style="color: #087f23; margin-right: 6px;">•</span>$1</div>');

    // Double line breaks
    safe = safe.replace(/\n\n+/g, '<div class="my-2"></div>');

    // Single line break
    safe = safe.replace(/\n/g, '<br>');

    return safe;
  }

  async function submitCopilotPrompt(event) {
    if (event) event.preventDefault();

    const input = document.getElementById('aiCopilotInput');
    const promptText = input.value.trim();
    if (!promptText) return;

    input.value = '';

    const stream = document.getElementById('aiChatStream');
    const body = document.getElementById('aiCopilotBody');

    // 1. Render User Bubble
    const userMsg = document.createElement('div');
    userMsg.className = 'ai-msg ai-msg-user';
    userMsg.textContent = promptText;
    stream.appendChild(userMsg);

    // 2. Render Loading Bubble
    const loadingMsg = document.createElement('div');
    loadingMsg.className = 'ai-msg ai-msg-assistant text-muted';
    loadingMsg.id = 'aiCopilotLoadingMsg';
    loadingMsg.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 12px; height: 12px;"></span> Sedang menganalisis & merumuskan perubahan...';
    stream.appendChild(loadingMsg);
    body.scrollTop = body.scrollHeight;

    // Toggle button state
    const sendBtn = document.getElementById('aiSendBtn');
    const sendIcon = document.getElementById('aiSendIcon');
    const sendSpinner = document.getElementById('aiSendSpinner');
    if (sendBtn) {
      sendBtn.disabled = true;
      sendIcon.classList.add('d-none');
      sendSpinner.classList.remove('d-none');
    }

    const items = collectRABData();
    const payload = {
      project_id: '<?= esc($project['uuid'] ?? $project['id'] ?? '') ?>',
      prompt: promptText,
      items: items,
      project_context: {
        project_name: '<?= esc($project['title'] ?? 'Proyek') ?>',
        building_type: '<?= esc($project['building_type'] ?? 'Rumah Tinggal') ?>',
        location: {
          province: '<?= esc($project['province'] ?? '') ?>',
          city_regency: '<?= esc($project['location'] ?? $project['city'] ?? 'Banyumas') ?>',
        },
        building_area_m2: parseFloat('<?= esc($project['building_area'] ?? '100') ?>') || 100,
        number_of_floors: parseInt('<?= esc($project['floors'] ?? '1') ?>') || 1,
      },
      history: copilotChatHistory.slice(-4)
    };

    try {
      const res = await fetch(AI_AGENT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const loadingEl = document.getElementById('aiCopilotLoadingMsg');
      if (loadingEl) loadingEl.remove();

      if (!res.ok) {
        throw new Error(`Server returned HTTP ${res.status}`);
      }

      const data = await res.json();

      // 3. Render Assistant Response (dengan markdown parser agar tidak ada raw bintang)
      const assistantMsg = document.createElement('div');
      assistantMsg.className = 'ai-msg ai-msg-assistant';
      assistantMsg.innerHTML = formatAiMarkdown(data.reply_message);
      stream.appendChild(assistantMsg);

      // Save to chat history
      copilotChatHistory.push({ role: 'user', content: promptText });
      copilotChatHistory.push({ role: 'assistant', content: data.reply_message });

      // 4. Render Proposed Actions if available
      if (data.actions && data.actions.length > 0) {
        currentProposedActions = data.actions;
        renderProposedActionsCard(data.actions, data.cost_impact);
      } else {
        document.getElementById('aiProposedActionsContainer').style.display = 'none';
      }

    } catch (err) {
      const loadingEl = document.getElementById('aiCopilotLoadingMsg');
      if (loadingEl) loadingEl.remove();

      const errMsg = document.createElement('div');
      errMsg.className = 'ai-msg ai-msg-assistant text-danger';
      errMsg.textContent = `Maaf, gagal memproses permintaan: ${err.message}. Pastikan service AI API di port 8200 aktif.`;
      stream.appendChild(errMsg);
    } finally {
      if (sendBtn) {
        sendBtn.disabled = false;
        sendIcon.classList.remove('d-none');
        sendSpinner.classList.add('d-none');
      }
      body.scrollTop = body.scrollHeight;
    }
  }

  function renderProposedActionsCard(actions, costImpact) {
    const container = document.getElementById('aiProposedActionsContainer');
    if (!container) return;

    let impactClass = 'cost-neutral';
    let impactText = 'Rp 0';
    if (costImpact > 0) {
      impactClass = 'cost-up';
      impactText = `+ Rp ${costImpact.toLocaleString('id-ID')}`;
    } else if (costImpact < 0) {
      impactClass = 'cost-down';
      impactText = `- Rp ${Math.abs(costImpact).toLocaleString('id-ID')}`;
    }

    let actionsHtml = actions.map((act, idx) => {
      let badgeClass = 'badge-update';
      let badgeText = 'UBAH';
      if (act.action_type === 'ADD_ITEM') {
        badgeClass = 'badge-add';
        badgeText = '+ TAMBAH';
      } else if (act.action_type === 'DELETE_ITEM') {
        badgeClass = 'badge-delete';
        badgeText = '- HAPUS';
      }

      const rowClick = act.target_item_id ? `onclick="highlightItemRow(${act.target_item_id})"` : '';
      const hoverTitle = act.target_item_id ? 'Klik untuk sorot baris tabel' : '';

      return `
        <div class="ai-action-item" ${rowClick} title="${hoverTitle}">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="ai-badge-act ${badgeClass}">${badgeText}</span>
            <span class="fw-bold tab-num ${act.cost_delta > 0 ? 'text-danger' : (act.cost_delta < 0 ? 'text-success' : 'text-muted')}" style="font-size: 11px;">
              ${act.cost_delta > 0 ? '+ Rp ' + act.cost_delta.toLocaleString('id-ID') : (act.cost_delta < 0 ? '- Rp ' + Math.abs(act.cost_delta).toLocaleString('id-ID') : 'Rp 0')}
            </span>
          </div>
          <div class="text-dark fw-medium" style="line-height: 1.4;">${act.description}</div>
          ${act.changes && act.changes.unit_price ? `<div class="text-muted small mt-1" style="font-size: 10px;">Harga Baru: Rp ${Number(act.changes.unit_price).toLocaleString('id-ID')} / ${act.changes.unit || ''}</div>` : ''}
        </div>
      `;
    }).join('');

    container.innerHTML = `
      <div class="ai-proposal-card">
        <div class="ai-proposal-header">
          <span>📝 Usulan Perubahan (${actions.length})</span>
          <span class="ai-cost-impact-pill ${impactClass}" title="Estimasi dampak ke total RAB">
            ${impactText}
          </span>
        </div>
        <div class="mb-2">
          ${actionsHtml}
        </div>
        <div class="d-flex gap-2 mt-2">
          <button type="button" class="btn btn-sm btn-success flex-grow-1 fw-bold rounded-pill" id="btnApplyActions" onclick="executeApplyProposedActions()" style="background: #087f23; border: none; font-size: 12px; padding: 7px 12px;">
            <span id="applyIcon">✓ Terapkan Perubahan</span>
            <span id="applySpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="cancelProposedActions()" style="font-size: 11.5px; padding: 7px 12px;">
            Batal
          </button>
        </div>
      </div>
    `;
    container.style.display = 'block';

    // Highlight all affected rows initially
    actions.forEach(act => {
      if (act.target_item_id) {
        const row = document.querySelector(`tr.rab-row-item[data-item-id="${act.target_item_id}"]`);
        if (row) row.classList.add('ai-target-highlight');
      }
    });
  }

  function cancelProposedActions() {
    clearTargetHighlights();
    const container = document.getElementById('aiProposedActionsContainer');
    if (container) container.style.display = 'none';
    currentProposedActions = [];
  }

  async function executeApplyProposedActions() {
    if (!currentProposedActions || currentProposedActions.length === 0) return;

    const btn = document.getElementById('btnApplyActions');
    const icon = document.getElementById('applyIcon');
    const spinner = document.getElementById('applySpinner');

    if (btn) {
      btn.disabled = true;
      icon.classList.add('d-none');
      spinner.classList.remove('d-none');
    }

    let successCount = 0;
    let failCount = 0;

    // Ambil default section jika ada ADD_ITEM tanpa section_id
    const firstSecRow = document.querySelector('tr.rab-sec-row');
    const defaultSectionId = firstSecRow ? parseInt(firstSecRow.dataset.secId) : null;

    for (const act of currentProposedActions) {
      try {
        if (act.action_type === 'UPDATE_ITEM') {
          const updatePayload = {};
          if (act.changes.item_name) updatePayload.item_name = act.changes.item_name;
          if (act.changes.volume !== undefined) updatePayload.volume = parseFloat(act.changes.volume);
          if (act.changes.unit) updatePayload.unit = act.changes.unit;
          if (act.changes.unit_price !== undefined) updatePayload.unit_price = parseFloat(act.changes.unit_price);
          if (act.changes.ahsp_code) updatePayload.ahsp_code = act.changes.ahsp_code;
          if (act.changes.ahsp_name) updatePayload.ahsp_name = act.changes.ahsp_name;

          const res = await fetch(`<?= base_url('api/estimation-items') ?>/${act.target_item_id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatePayload)
          });
          if (res.ok) successCount++; else failCount++;

        } else if (act.action_type === 'ADD_ITEM') {
          let secId = act.target_section_id;
          if (!secId && act.target_category) {
            // Cari section berdasarkan nama kategori
            const secRow = Array.from(document.querySelectorAll('tr.rab-sec-row')).find(r => {
              const t = r.querySelector('.rab-sec-title')?.textContent?.trim() || '';
              return t.toLowerCase().includes(act.target_category.toLowerCase());
            });
            if (secRow) secId = parseInt(secRow.dataset.secId);
          }
          if (!secId) secId = defaultSectionId;

          const addPayload = {
            section_id: secId,
            item_name: act.changes.item_name || act.description,
            volume: parseFloat(act.changes.volume) || 1.0,
            unit: act.changes.unit || 'm2',
            unit_price: parseFloat(act.changes.unit_price) || 0,
            ahsp_code: act.changes.ahsp_code || null,
            ahsp_status: act.changes.ahsp_code ? 'mapped_high' : 'unmapped'
          };

          const res = await fetch(`<?= base_url('api/estimation-items') ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(addPayload)
          });
          if (res.ok) successCount++; else failCount++;

        } else if (act.action_type === 'DELETE_ITEM') {
          const res = await fetch(`<?= base_url('api/estimation-items') ?>/${act.target_item_id}`, {
            method: 'DELETE'
          });
          if (res.ok) successCount++; else failCount++;
        }
      } catch (err) {
        failCount++;
      }
    }

    if (successCount > 0) {
      showToast('Berhasil', `${successCount} perubahan berhasil diterapkan ke RAB. Memuat ulang tabel...`, 'success');
      setTimeout(() => location.reload(), 700);
    } else {
      showToast('Gagal', `Gagal menerapkan perubahan (${failCount} kesalahan).`, 'error');
      if (btn) {
        btn.disabled = false;
        icon.classList.remove('d-none');
        spinner.classList.add('d-none');
      }
    }
  }

  async function runAudit() {
    const items = collectRABData();
    if (items.length === 0) {
      showToast('Kosong', 'Tidak ada item pekerjaan untuk diaudit.', 'warning');
      return;
    }

    // Show loading
    document.getElementById('aiAuditInitial').style.display = 'none';
    document.getElementById('aiAuditResults').style.display = 'none';
    document.getElementById('aiAuditLoading').style.display = 'block';
    document.getElementById('aiDrawerFooter').style.display = 'none';
    clearAuditHighlights();

    // Open drawer if not open
    openAuditDrawer();

    const payload = {
      project_id: '<?= esc($project['uuid'] ?? $project['id'] ?? '') ?>',
      project_context: {
        project_name: '<?= esc($project['title'] ?? 'Proyek') ?>',
        building_type: '<?= esc($project['building_type'] ?? 'Rumah Tinggal') ?>',
        location: {
          province: '<?= esc($project['province'] ?? '') ?>',
          city_regency: '<?= esc($project['location'] ?? $project['city'] ?? 'Banyumas') ?>',
        },
        building_area_m2: parseFloat('<?= esc($project['building_area'] ?? '100') ?>') || 100,
        number_of_floors: parseInt('<?= esc($project['floors'] ?? '1') ?>') || 1,
        currency: 'IDR',
      },
      items: items,
    };

    try {
      const res = await fetch(AI_AUDIT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        throw new Error(`Server error: ${res.status}`);
      }

      const result = await res.json();
      lastAuditResult = result;
      renderAuditResults(result);
      applyAuditHighlights(result);

    } catch (err) {
      document.getElementById('aiAuditLoading').style.display = 'none';
      document.getElementById('aiAuditResults').style.display = 'block';
      document.getElementById('aiAuditResults').innerHTML = `
        <div class="text-center" style="padding: 40px 20px;">
          <div style="font-size: 40px; margin-bottom: 12px;">⚠️</div>
          <h6 style="font-weight: 700; color: #dc2626;">Audit Gagal</h6>
          <p style="font-size: 12px; color: #64748b; margin-top: 8px;">${err.message}</p>
          <button type="button" class="btn-reaudit" style="margin-top: 16px; padding: 8px 20px; border-radius: 8px; font-size: 12.5px;" onclick="runAudit()">Coba Lagi</button>
        </div>
      `;
    }
  }

  function renderAuditResults(result) {
    const data = result.data;
    const score = data.health_score;
    const status = data.health_status;
    const summary = data.summary;

    // Score color
    let scoreColor = '#16a34a'; // green
    if (score < 50) scoreColor = '#dc2626';
    else if (score < 75) scoreColor = '#d97706';
    else if (score < 90) scoreColor = '#65a30d';

    // Status text
    const statusMap = {
      'EXCELLENT': 'Sangat Baik ✅',
      'GOOD': 'Baik 👍',
      'NEEDS_REVIEW': 'Perlu Ditinjau ⚠️',
      'POOR': 'Perlu Perbaikan 🔴',
    };
    const statusText = statusMap[status] || status;

    // Ring gauge: circumference = 2πr = 2 × π × 48 ≈ 301.6
    const circumference = 301.6;
    const offset = circumference - (score / 100) * circumference;

    // Update FAB badge
    const totalIssues = summary.critical_count + summary.warning_count + summary.missing_scope_count;
    const fabBadge = document.getElementById('aiFabBadge');
    if (totalIssues > 0) {
      fabBadge.textContent = totalIssues;
      fabBadge.style.display = 'inline-block';
    } else {
      fabBadge.style.display = 'none';
    }

    let html = `
      <!-- Health Score Gauge -->
      <div class="ai-score-container">
        <div class="ai-score-ring">
          <svg viewBox="0 0 120 120">
            <circle class="ring-bg" cx="60" cy="60" r="48"/>
            <circle class="ring-fill" cx="60" cy="60" r="48"
              stroke="${scoreColor}"
              stroke-dasharray="${circumference}"
              stroke-dashoffset="${offset}"/>
          </svg>
          <div class="ai-score-number">
            <span class="score-val" style="color: ${scoreColor}">${score}</span>
            <span class="score-max">/ 100</span>
          </div>
        </div>
        <div class="ai-score-status" style="color: ${scoreColor}">${statusText}</div>
      </div>

      <!-- Summary Pills -->
      <div class="ai-summary-row">
        <span class="ai-summary-pill ${summary.critical_count > 0 ? 'pill-critical' : 'pill-ok'}">
          ${summary.critical_count > 0 ? '🔴' : '✅'} ${summary.critical_count} Kritis
        </span>
        <span class="ai-summary-pill ${summary.warning_count > 0 ? 'pill-warning' : 'pill-ok'}">
          ${summary.warning_count > 0 ? '🟡' : '✅'} ${summary.warning_count} Peringatan
        </span>
        <span class="ai-summary-pill ${summary.missing_scope_count > 0 ? 'pill-missing' : 'pill-ok'}">
          ${summary.missing_scope_count > 0 ? '⚠️' : '✅'} ${summary.missing_scope_count} Item Hilang
        </span>
      </div>

      <div style="font-size: 11.5px; text-align: center; color: #94a3b8; margin-bottom: 16px;">
        ${summary.total_items_checked} item diperiksa · ${data.audit_timestamp}
      </div>
    `;

    // Anomaly detail cards
    if (data.anomalies && data.anomalies.length > 0) {
      html += '<div class="ai-section-title">Detail Anomali</div>';
      data.anomalies.forEach(a => {
        const sevClass = a.severity === 'CRITICAL' ? 'critical' : 'warning';
        const sevIcon = a.severity === 'CRITICAL' ? '🔴' : '🟡';
        const priceInfo = a.benchmark_value
          ? `<div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Acuan: Rp ${Number(a.benchmark_value).toLocaleString('id-ID')} · Deviasi: ${a.deviation_percent > 0 ? '+' : ''}${a.deviation_percent}%</div>`
          : '';
        html += `
          <div class="ai-finding-card" onclick="scrollToItem(${a.item_id})">
            <div class="card-severity ${sevClass}">${sevIcon} ${a.type.replace(/_/g, ' ')} · Item #${a.item_id}</div>
            <div class="card-message">${a.message}</div>
            ${priceInfo}
            ${a.recommendation ? `<div class="card-recommend">💡 ${a.recommendation}</div>` : ''}
          </div>
        `;
      });
    }

    // Missing Scope cards
    if (data.missing_scopes && data.missing_scopes.length > 0) {
      html += '<div class="ai-section-title">Item Pekerjaan Hilang</div>';
      data.missing_scopes.forEach(ms => {
        html += `
          <div class="ai-finding-card">
            <div class="card-severity missing">⚠️ MISSING SCOPE · ${ms.category}</div>
            <div class="card-message">Belum ada: <strong>${ms.missing_item}</strong></div>
            <div class="card-recommend">💡 ${ms.reason}</div>
            <div style="font-size: 10.5px; color: #94a3b8; margin-top: 4px;">Keyakinan: ${Math.round(ms.confidence * 100)}%</div>
          </div>
        `;
      });
    }

    if (totalIssues === 0) {
      html += `
        <div class="text-center" style="padding: 20px;">
          <div style="font-size: 40px;">🎉</div>
          <p style="font-size: 13px; color: #16a34a; font-weight: 600; margin-top: 8px;">RAB dalam kondisi sangat baik!</p>
          <p style="font-size: 12px; color: #64748b;">Tidak ditemukan anomali atau item yang hilang.</p>
        </div>
      `;
    }

    document.getElementById('aiAuditLoading').style.display = 'none';
    document.getElementById('aiAuditResults').innerHTML = html;
    document.getElementById('aiAuditResults').style.display = 'block';
    document.getElementById('aiDrawerFooter').style.display = 'flex';
  }

  function scrollToItem(itemId) {
    const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
    if (row) {
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      row.style.transition = 'outline 0.2s';
      row.style.outline = '2px solid #087f23';
      setTimeout(() => { row.style.outline = 'none'; }, 2000);
    }
  }

  function applyAuditHighlights(result) {
    const data = result.data;
    if (!data.anomalies) return;

    data.anomalies.forEach(a => {
      const row = document.querySelector(`tr[data-item-id="${a.item_id}"]`);
      if (!row) return;

      // Apply row tint
      if (a.severity === 'CRITICAL') {
        row.classList.add('audit-critical');
      } else {
        row.classList.add('audit-warning');
      }

      // Add badge to relevant cell
      const cells = row.querySelectorAll('td');
      let targetCell = null;
      if (a.field === 'volume' && cells[3]) targetCell = cells[3];
      else if (a.field === 'unit_price' && cells[5]) targetCell = cells[5];

      if (targetCell && !targetCell.querySelector('.audit-badge')) {
        const badge = document.createElement('span');
        badge.className = `audit-badge badge-${a.severity === 'CRITICAL' ? 'critical' : 'warning'}`;
        badge.textContent = a.severity === 'CRITICAL' ? '🔴' : '🟡';
        badge.title = a.message;
        targetCell.appendChild(badge);
      }
    });
  }

  function clearAuditHighlights() {
    document.querySelectorAll('.audit-critical, .audit-warning').forEach(el => {
      el.classList.remove('audit-critical', 'audit-warning');
    });
    document.querySelectorAll('.audit-badge').forEach(el => el.remove());
  }
</script>
<?= $this->endSection() ?>
