<div>
<style>
    /* ══════════ FONDATIONS ══════════ */
    :root {
        --ach-bg: #14b9fa28;
        --ach-sidebar-from: #14b9fa;
        --ach-sidebar-to: #cce9eb;
        --ach-card-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        --ach-card-hover: 0 10px 25px -5px rgba(0,0,0,0.08);
        --ach-radius: 1rem;
        --ach-transition: all 0.25s cubic-bezier(.4,0,.2,1);
    }

    .fi-body { background: var(--ach-bg) !important; }

    /* ══════════ SIDEBAR GRADIENT ══════════ */
    .fi-sidebar {
        background: linear-gradient(180deg, var(--ach-sidebar-from) 0%, var(--ach-sidebar-to) 100%) !important;
    }
    .fi-sidebar-header {
        border-bottom: 1px solid rgba(255,255,255,0.08) !important;
    }
    .fi-sidebar-nav .fi-sidebar-item {
        border-radius: 0.625rem;
        margin: 2px 8px;
        transition: var(--ach-transition);
    }
    .fi-sidebar-nav .fi-sidebar-item:hover {
        background: rgba(226, 226, 226, 0.08) !important;
    }
    .fi-sidebar-nav .fi-sidebar-item.fi-active {
        background: rgba(255, 255, 255, 0.938) !important;
        border-left: 3px solid #818cf8;
    }
    .fi-sidebar-group-label {
        text-transform: uppercase;
        font-size: 0.65rem !important;
        letter-spacing: 0.08em;
        opacity: 0.5;
        padding: 0.5rem 1rem !important;
    }

    /* ══════════ BANDEAU TRICOLORE CAMEROUNAIS ══════════ */
    .fi-simple-layout::before {
        content: '';
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 4px;
        z-index: 9999;
        background: linear-gradient(90deg,
            #009639 0%, #009639 33.33%,
            #CE1126 33.33%, #CE1126 66.66%,
            #FCD116 66.66%, #FCD116 100%
        );
    }

    /* ══════════ CARDS & WIDGETS ══════════ */
    .fi-wi-stats-overview-stat,
    .fi-wi-chart,
    .fi-section,
    .fi-ta {
        border-radius: var(--ach-radius) !important;
        box-shadow: var(--ach-card-shadow) !important;
        border: 1px solid rgba(0,0,0,0.04) !important;
        overflow: hidden;
        transition: var(--ach-transition);
    }
    .fi-wi-stats-overview-stat:hover,
    .fi-wi-chart:hover {
        box-shadow: var(--ach-card-hover) !important;
        transform: translateY(-2px);
    }

    /* ══════════ KPI STAT CARDS ══════════ */
    .fi-wi-stats-overview-stat:nth-child(1) { border-top: 3px solid #6366f1 !important; }
    .fi-wi-stats-overview-stat:nth-child(2) { border-top: 3px solid #10b981 !important; }
    .fi-wi-stats-overview-stat:nth-child(3) { border-top: 3px solid #f43f5e !important; }
    .fi-wi-stats-overview-stat:nth-child(4) { border-top: 3px solid #8b5cf6 !important; }
    .fi-wi-stats-overview-stat:nth-child(5) { border-top: 3px solid #0ea5e9 !important; }
    .fi-wi-stats-overview-stat:nth-child(6) { border-top: 3px solid #f59e0b !important; }

    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-value {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
    }

    /* ══════════ TABLE ══════════ */
    .fi-ta-table { font-size: 0.8125rem !important; }
    .fi-ta-row { transition: background 0.15s ease; }
    .fi-ta-row:hover { background: rgba(99,102,241,0.03) !important; }
    .fi-ta-header-cell {
        text-transform: uppercase !important;
        font-size: 0.6875rem !important;
        letter-spacing: 0.05em !important;
        color: #64748b !important;
        font-weight: 600 !important;
    }

    /* ══════════ BADGES ══════════ */
    .fi-badge {
        font-weight: 600 !important;
        letter-spacing: 0.02em !important;
        border-radius: 0.5rem !important;
    }

    /* ══════════ FORMULAIRES ══════════ */
    .fi-fo-field-wrp .fi-input {
        border-radius: 0.625rem !important;
        transition: var(--ach-transition);
    }
    .fi-fo-field-wrp .fi-input:focus-within {
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
    }
    .fi-fo-field-wrp .fi-select {
        border-radius: 0.625rem !important;
    }

    /* ══════════ BOUTONS ══════════ */
    .fi-btn {
        border-radius: 0.625rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em;
        transition: var(--ach-transition) !important;
    }
    .fi-btn:hover {
        transform: translateY(-1px);
    }

    /* ══════════ MODAL ══════════ */
    .fi-modal-content {
        border-radius: var(--ach-radius) !important;
    }

    /* ══════════ TOPBAR ══════════ */
    .fi-topbar {
        backdrop-filter: blur(12px);
        background: rgba(255,255,255,0.85) !important;
        border-bottom: 1px solid rgba(0,0,0,0.06) !important;
    }

    /* ══════════ NOTIFICATIONS ══════════ */
    .fi-no {
        border-radius: 0.75rem !important;
    }

    /* ══════════ SCROLLBAR ══════════ */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.2); }

    /* ══════════ ANIMATIONS ══════════ */
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fi-page > * { animation: fadeSlideUp 0.35s ease-out; }
    .fi-wi-stats-overview-stat { animation: fadeSlideUp 0.4s ease-out; }

    /* ══════════ CUSTOM DOSSIER ROWS ══════════ */
    .border-l-investissement { border-left: 4px solid #6366f1 !important; }
    .border-l-fonctionnement { border-left: 4px solid #10b981 !important; }
    .border-l-nopdf { border-left: 4px solid #f43f5e !important; background: rgba(244,63,94,0.02) !important; }

    /* ══════════ PDF VIEWER ══════════ */
    .pdf-embed-container {
        border-radius: var(--ach-radius);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.08);
        box-shadow: var(--ach-card-shadow);
    }
    .pdf-embed-container iframe {
        width: 100%;
        height: 600px;
        border: none;
    }
</style>
</div>
