<div>
<style>
    /* ══════════════════════════════════════════════════════════════
       ArchiCompta Pro — Premium Finance Theme v3
       Sidebar finance pro + contraste amélioré + dark mode
       ══════════════════════════════════════════════════════════════ */

    :root {
        --ach-bg: #eef2f7;
        --ach-sidebar-from: #0f1d3a;
        --ach-sidebar-to: #162044;
        --ach-accent-gold: #d4a853;
        --ach-accent-blue: #3b82f6;
        --ach-card-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 1px 2px rgba(0,0,0,0.04);
        --ach-card-hover: 0 10px 25px -5px rgba(0,0,0,0.1);
        --ach-radius: 0.875rem;
        --ach-transition: all 0.2s cubic-bezier(.4,0,.2,1);
    }

    .fi-body { background: var(--ach-bg) !important; }
    .dark .fi-body { background: #080e1a !important; }

    /* ══════════════════════════════════════════════════════════════
       SIDEBAR — FINANCE PREMIUM
       Bleu marine profond + liserés dorés
       ══════════════════════════════════════════════════════════════ */
    .fi-sidebar {
        background: linear-gradient(180deg, var(--ach-sidebar-from) 0%, var(--ach-sidebar-to) 100%) !important;
        border-right: 1px solid rgba(212, 168, 83, 0.15) !important;
    }
    .dark .fi-sidebar {
        background: linear-gradient(180deg, #060c1a 0%, #0a1128 100%) !important;
    }

    /* Brand header */
    .fi-sidebar-header {
        border-bottom: 1px solid rgba(212, 168, 83, 0.2) !important;
        padding-bottom: 0.75rem !important;
    }
    .fi-sidebar-header a,
    .fi-sidebar-header span {
        color: #ffffff !important;
        font-weight: 800 !important;
        letter-spacing: 0.02em;
    }

    /* ── Éléments de navigation ── */
    .fi-sidebar-nav .fi-sidebar-item {
        border-radius: 0.5rem;
        margin: 1px 6px;
        transition: var(--ach-transition);
    }

    /* Icônes : BLANCHES */
    .fi-sidebar-item-icon {
        color: #ffffff !important;
        opacity: 0.85;
        transition: var(--ach-transition);
    }

    /* Texte normal : blanc lumineux */
    .fi-sidebar-item-button,
    .fi-sidebar-item-label {
        color: #e2e8f0 !important;
        font-weight: 500 !important;
        font-size: 0.84rem !important;
        transition: var(--ach-transition);
    }

    /* ── HOVER : fond clair, texte NOIR ── */
    .fi-sidebar-nav .fi-sidebar-item:hover {
        background: rgba(255, 255, 255, 0.92) !important;
    }
    .fi-sidebar-nav .fi-sidebar-item:hover .fi-sidebar-item-button,
    .fi-sidebar-nav .fi-sidebar-item:hover .fi-sidebar-item-label {
        color: #0f172a !important;
        font-weight: 600 !important;
    }
    .fi-sidebar-nav .fi-sidebar-item:hover .fi-sidebar-item-icon {
        color: var(--ach-accent-blue) !important;
        opacity: 1;
    }

    /* ── ACTIF : bande dorée + fond semi-transparent ── */
    .fi-sidebar-nav .fi-sidebar-item.fi-active {
        background: rgba(212, 168, 83, 0.12) !important;
        border-left: 3px solid var(--ach-accent-gold) !important;
    }
    .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-button,
    .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-label {
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
        color: var(--ach-accent-gold) !important;
        opacity: 1;
    }

    /* ── Labels de groupe (sections) — style finance ── */
    .fi-sidebar-group-label {
        text-transform: uppercase;
        font-size: 0.62rem !important;
        letter-spacing: 0.14em;
        color: var(--ach-accent-gold) !important;
        font-weight: 700 !important;
        padding: 0.9rem 0.75rem 0.3rem !important;
        position: relative;
    }
    /* Ligne décorative sous les labels de groupe */
    .fi-sidebar-group-label::after {
        content: '';
        display: block;
        width: 28px;
        height: 2px;
        background: var(--ach-accent-gold);
        opacity: 0.4;
        margin-top: 4px;
        border-radius: 1px;
    }

    /* Icônes de groupe */
    .fi-sidebar-group-icon {
        color: var(--ach-accent-gold) !important;
        opacity: 0.7;
    }

    /* ── Badges dans la sidebar (compteurs) ── */
    .fi-sidebar-item-badge {
        font-weight: 700 !important;
        font-size: 0.68rem !important;
        min-width: 20px;
        text-align: center;
    }

    /* ══════════════════════════════════════════════════════════════
       BANDEAU TRICOLORE CAMEROUNAIS
       ══════════════════════════════════════════════════════════════ */
    .fi-simple-layout::before,
    .fi-topbar::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        z-index: 9999;
        background: linear-gradient(90deg,
            #009639 0%, #009639 33.33%,
            #CE1126 33.33%, #CE1126 66.66%,
            #FCD116 66.66%, #FCD116 100%
        );
    }
    .fi-topbar { position: relative; }

    /* ══════════════════════════════════════════════════════════════
       TOPBAR
       ══════════════════════════════════════════════════════════════ */
    .fi-topbar {
        backdrop-filter: blur(12px);
        background: rgba(255,255,255,0.95) !important;
        border-bottom: 1px solid rgba(0,0,0,0.06) !important;
    }
    .dark .fi-topbar {
        background: rgba(15, 23, 42, 0.95) !important;
        border-bottom-color: rgba(255,255,255,0.06) !important;
    }

    /* ══════════════════════════════════════════════════════════════
       TEXTES — CONTRASTE
       ══════════════════════════════════════════════════════════════ */
    .fi-header-heading {
        color: #0f172a !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
    }
    .dark .fi-header-heading { color: #f1f5f9 !important; }

    .fi-header-subheading, .fi-header .text-sm {
        color: #475569 !important;
        font-weight: 500 !important;
    }
    .dark .fi-header-subheading, .dark .fi-header .text-sm {
        color: #cbd5e1 !important;
    }

    .fi-section-header-heading {
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 1.05rem !important;
    }
    .dark .fi-section-header-heading { color: #e2e8f0 !important; }

    .fi-section-header-description { color: #64748b !important; }
    .dark .fi-section-header-description { color: #94a3b8 !important; }

    .fi-fo-field-wrp label,
    .fi-fo-field-wrp .fi-fo-field-wrp-label {
        color: #1e293b !important;
        font-weight: 600 !important;
    }
    .dark .fi-fo-field-wrp label,
    .dark .fi-fo-field-wrp .fi-fo-field-wrp-label { color: #e2e8f0 !important; }

    .fi-fo-field-wrp .fi-fo-field-wrp-helper-text { color: #64748b !important; }
    .dark .fi-fo-field-wrp .fi-fo-field-wrp-helper-text { color: #94a3b8 !important; }

    /* Liens */
    a:not(.fi-btn):not(.fi-sidebar-item-button):not(.fi-sidebar-header *) { color: #4f46e5; }
    .dark a:not(.fi-btn):not(.fi-sidebar-item-button):not(.fi-sidebar-header *) { color: #818cf8; }

    /* Breadcrumbs */
    .fi-breadcrumbs a { color: #4f46e5 !important; font-weight: 500; }
    .dark .fi-breadcrumbs a { color: #818cf8 !important; }

    /* ══════════════════════════════════════════════════════════════
       KPI / STATS WIDGETS
       ══════════════════════════════════════════════════════════════ */
    .fi-wi-stats-overview-stat,
    .fi-wi-chart, .fi-section, .fi-ta {
        border-radius: var(--ach-radius) !important;
        box-shadow: var(--ach-card-shadow) !important;
        border: 1px solid rgba(0,0,0,0.05) !important;
        overflow: hidden;
        transition: var(--ach-transition);
    }
    .dark .fi-wi-stats-overview-stat,
    .dark .fi-wi-chart, .dark .fi-section, .dark .fi-ta {
        border-color: rgba(255,255,255,0.06) !important;
    }

    .fi-wi-stats-overview-stat:hover, .fi-wi-chart:hover {
        box-shadow: var(--ach-card-hover) !important;
        transform: translateY(-2px);
    }

    .fi-wi-stats-overview-stat:nth-child(1) { border-top: 3px solid #6366f1 !important; }
    .fi-wi-stats-overview-stat:nth-child(2) { border-top: 3px solid #10b981 !important; }
    .fi-wi-stats-overview-stat:nth-child(3) { border-top: 3px solid #f43f5e !important; }
    .fi-wi-stats-overview-stat:nth-child(4) { border-top: 3px solid #8b5cf6 !important; }
    .fi-wi-stats-overview-stat:nth-child(5) { border-top: 3px solid #0ea5e9 !important; }
    .fi-wi-stats-overview-stat:nth-child(6) { border-top: 3px solid var(--ach-accent-gold) !important; }

    .fi-wi-stats-overview-stat-value {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        color: #0f172a !important;
    }
    .dark .fi-wi-stats-overview-stat-value { color: #f8fafc !important; }

    .fi-wi-stats-overview-stat-label {
        color: #475569 !important;
        font-weight: 600 !important;
        font-size: 0.8rem !important;
    }
    .dark .fi-wi-stats-overview-stat-label { color: #94a3b8 !important; }

    .fi-wi-stats-overview-stat-description { font-weight: 600 !important; }

    /* ══════════════════════════════════════════════════════════════
       TABLE
       ══════════════════════════════════════════════════════════════ */
    .fi-ta-table { font-size: 0.8125rem !important; }
    .fi-ta-row { transition: background 0.15s ease; }
    .fi-ta-row:hover { background: rgba(99,102,241,0.04) !important; }
    .dark .fi-ta-row:hover { background: rgba(99,102,241,0.08) !important; }

    .fi-ta-header-cell {
        text-transform: uppercase !important;
        font-size: 0.6875rem !important;
        letter-spacing: 0.05em !important;
        color: #475569 !important;
        font-weight: 700 !important;
    }
    .dark .fi-ta-header-cell { color: #94a3b8 !important; }

    .fi-ta-cell { color: #1e293b !important; }
    .dark .fi-ta-cell { color: #e2e8f0 !important; }

    .fi-ta-empty-state-heading { color: #475569 !important; font-weight: 600 !important; }
    .dark .fi-ta-empty-state-heading { color: #94a3b8 !important; }

    /* ══════════════════════════════════════════════════════════════
       BADGES, FORMULAIRES, BOUTONS
       ══════════════════════════════════════════════════════════════ */
    .fi-badge { font-weight: 700 !important; border-radius: 0.5rem !important; }

    .fi-fo-field-wrp .fi-input, .fi-fo-field-wrp .fi-select {
        border-radius: 0.625rem !important;
        transition: var(--ach-transition);
    }
    .fi-fo-field-wrp .fi-input:focus-within {
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
        border-color: #6366f1 !important;
    }
    .dark .fi-fo-field-wrp .fi-input:focus-within {
        box-shadow: 0 0 0 3px rgba(129,140,248,0.2) !important;
    }

    .fi-btn {
        border-radius: 0.625rem !important;
        font-weight: 600 !important;
        transition: var(--ach-transition) !important;
    }
    .fi-btn:hover { transform: translateY(-1px); }

    /* ══════════════════════════════════════════════════════════════
       MODAL, INFOLIST, NOTIFICATIONS
       ══════════════════════════════════════════════════════════════ */
    .fi-modal-content { border-radius: var(--ach-radius) !important; }
    .fi-modal-heading { color: #0f172a !important; font-weight: 700 !important; }
    .dark .fi-modal-heading { color: #f1f5f9 !important; }

    .fi-in-entry-wrp-label {
        color: #64748b !important; font-weight: 600 !important;
        font-size: 0.75rem !important; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .dark .fi-in-entry-wrp-label { color: #94a3b8 !important; }
    .fi-in-text { color: #0f172a !important; font-weight: 500 !important; }
    .dark .fi-in-text { color: #e2e8f0 !important; }

    .fi-no { border-radius: 0.75rem !important; }
    .fi-no-title { font-weight: 700 !important; }

    /* ══════════════════════════════════════════════════════════════
       SCROLLBAR, ANIMATIONS
       ══════════════════════════════════════════════════════════════ */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 3px; }
    .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); }

    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fi-page > * { animation: fadeSlideUp 0.3s ease-out; }
    .fi-wi-stats-overview-stat { animation: fadeSlideUp 0.35s ease-out; }

    /* ══════════════════════════════════════════════════════════════
       BORDURES DOSSIER + PDF VIEWER
       ══════════════════════════════════════════════════════════════ */
    .border-l-investissement { border-left: 4px solid #6366f1 !important; }
    .border-l-fonctionnement { border-left: 4px solid #10b981 !important; }
    .border-l-nopdf {
        border-left: 4px solid #f43f5e !important;
        background: rgba(244,63,94,0.03) !important;
    }
    .dark .border-l-nopdf { background: rgba(244,63,94,0.06) !important; }

    .pdf-embed-container {
        border-radius: var(--ach-radius); overflow: hidden;
        border: 1px solid rgba(0,0,0,0.08); box-shadow: var(--ach-card-shadow);
    }
    .pdf-embed-container iframe { width: 100%; height: 600px; border: none; }
</style>
</div>
