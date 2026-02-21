<div>
<style>
    /* ══════════════════════════════════════════════════════════════
       ArchiCompta Pro — Premium Styles
       Compatible : Light mode, Dark mode, System theme
       ══════════════════════════════════════════════════════════════ */

    /* ══════════ FONDATIONS ══════════ */
    :root {
        --ach-bg: #f0f4f8;
        --ach-sidebar-from: #1e293b;
        --ach-sidebar-to: #0f172a;
        --ach-card-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.05);
        --ach-card-hover: 0 10px 25px -5px rgba(0,0,0,0.1);
        --ach-radius: 1rem;
        --ach-transition: all 0.25s cubic-bezier(.4,0,.2,1);
    }

    .fi-body {
        background: var(--ach-bg) !important;
    }

    .dark .fi-body {
        background: #0c1222 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       TEXTES — CONTRASTE AMÉLIORÉ (light + dark)
       ══════════════════════════════════════════════════════════════ */

    /* Titres de page (h1) */
    .fi-header-heading {
        color: #0f172a !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
    }
    .dark .fi-header-heading {
        color: #f1f5f9 !important;
    }

    /* Sous-titres / descriptions de page */
    .fi-header-subheading,
    .fi-header .text-sm {
        color: #475569 !important;
        font-weight: 500 !important;
    }
    .dark .fi-header-subheading,
    .dark .fi-header .text-sm {
        color: #cbd5e1 !important;
    }

    /* Titres de sections dans les formulaires */
    .fi-section-header-heading {
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 1.05rem !important;
    }
    .dark .fi-section-header-heading {
        color: #e2e8f0 !important;
    }

    /* Descriptions de sections */
    .fi-section-header-description {
        color: #64748b !important;
    }
    .dark .fi-section-header-description {
        color: #94a3b8 !important;
    }

    /* Labels des champs de formulaire */
    .fi-fo-field-wrp label,
    .fi-fo-field-wrp .fi-fo-field-wrp-label {
        color: #1e293b !important;
        font-weight: 600 !important;
    }
    .dark .fi-fo-field-wrp label,
    .dark .fi-fo-field-wrp .fi-fo-field-wrp-label {
        color: #e2e8f0 !important;
    }

    /* Texte helper sous les champs */
    .fi-fo-field-wrp .fi-fo-field-wrp-helper-text {
        color: #64748b !important;
    }
    .dark .fi-fo-field-wrp .fi-fo-field-wrp-helper-text {
        color: #94a3b8 !important;
    }

    /* Texte des inputs */
    .fi-input input,
    .fi-input textarea,
    .fi-select select {
        color: #0f172a !important;
    }
    .dark .fi-input input,
    .dark .fi-input textarea,
    .dark .fi-select select {
        color: #f1f5f9 !important;
    }

    /* Liens */
    a:not(.fi-btn):not(.fi-sidebar-item-button) {
        color: #4f46e5;
    }
    a:not(.fi-btn):not(.fi-sidebar-item-button):hover {
        color: #4338ca;
    }
    .dark a:not(.fi-btn):not(.fi-sidebar-item-button) {
        color: #818cf8;
    }
    .dark a:not(.fi-btn):not(.fi-sidebar-item-button):hover {
        color: #a5b4fc;
    }

    /* Breadcrumbs */
    .fi-breadcrumbs li {
        color: #64748b !important;
    }
    .fi-breadcrumbs a {
        color: #4f46e5 !important;
        font-weight: 500;
    }
    .dark .fi-breadcrumbs li {
        color: #94a3b8 !important;
    }
    .dark .fi-breadcrumbs a {
        color: #818cf8 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       SIDEBAR — GRADIENT SOMBRE PRO
       ══════════════════════════════════════════════════════════════ */
    .fi-sidebar {
        background: linear-gradient(180deg, var(--ach-sidebar-from) 0%, var(--ach-sidebar-to) 100%) !important;
    }
    .dark .fi-sidebar {
        background: linear-gradient(180deg, #0f172a 0%, #020617 100%) !important;
    }

    .fi-sidebar-header {
        border-bottom: 1px solid rgba(255,255,255,0.08) !important;
    }

    /* Brand name dans la sidebar */
    .fi-sidebar-header a,
    .fi-sidebar-header span {
        color: #ffffff !important;
        font-weight: 700 !important;
    }

    /* Éléments de navigation sidebar */
    .fi-sidebar-nav .fi-sidebar-item {
        border-radius: 0.625rem;
        margin: 2px 8px;
        transition: var(--ach-transition);
    }

    /* Texte des items sidebar — BIEN VISIBLE */
    .fi-sidebar-item-button {
        color: #cbd5e1 !important;
    }
    .fi-sidebar-item-button:hover {
        color: #ffffff !important;
    }
    .fi-sidebar-item-label {
        color: #cbd5e1 !important;
        font-weight: 500 !important;
        font-size: 0.875rem !important;
    }

    /* Icônes sidebar */
    .fi-sidebar-item-icon {
        color: #94a3b8 !important;
    }
    .fi-sidebar-item-button:hover .fi-sidebar-item-icon {
        color: #e2e8f0 !important;
    }

    /* Item actif */
    .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-button,
    .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-label {
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .fi-sidebar-nav .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
        color: #818cf8 !important;
    }
    .fi-sidebar-nav .fi-sidebar-item.fi-active {
        background: rgba(99, 102, 241, 0.2) !important;
        border-left: 3px solid #818cf8;
    }

    /* Hover */
    .fi-sidebar-nav .fi-sidebar-item:hover {
        background: rgba(255, 255, 255, 0.06) !important;
    }

    /* Labels de groupe */
    .fi-sidebar-group-label {
        text-transform: uppercase;
        font-size: 0.65rem !important;
        letter-spacing: 0.1em;
        color: #64748b !important;
        font-weight: 600 !important;
        padding: 0.75rem 1rem 0.25rem !important;
    }

    /* Badges de navigation (compteurs) */
    .fi-sidebar-item-badge {
        font-weight: 700 !important;
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
        background: rgba(255,255,255,0.92) !important;
        border-bottom: 1px solid rgba(0,0,0,0.06) !important;
    }
    .dark .fi-topbar {
        background: rgba(15, 23, 42, 0.92) !important;
        border-bottom: 1px solid rgba(255,255,255,0.06) !important;
    }

    /* ══════════════════════════════════════════════════════════════
       KPI / STATS WIDGETS
       ══════════════════════════════════════════════════════════════ */
    .fi-wi-stats-overview-stat,
    .fi-wi-chart,
    .fi-section,
    .fi-ta {
        border-radius: var(--ach-radius) !important;
        box-shadow: var(--ach-card-shadow) !important;
        border: 1px solid rgba(0,0,0,0.06) !important;
        overflow: hidden;
        transition: var(--ach-transition);
    }
    .dark .fi-wi-stats-overview-stat,
    .dark .fi-wi-chart,
    .dark .fi-section,
    .dark .fi-ta {
        border-color: rgba(255,255,255,0.06) !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
    }

    .fi-wi-stats-overview-stat:hover,
    .fi-wi-chart:hover {
        box-shadow: var(--ach-card-hover) !important;
        transform: translateY(-2px);
    }

    /* Bordure colorée par position */
    .fi-wi-stats-overview-stat:nth-child(1) { border-top: 3px solid #6366f1 !important; }
    .fi-wi-stats-overview-stat:nth-child(2) { border-top: 3px solid #10b981 !important; }
    .fi-wi-stats-overview-stat:nth-child(3) { border-top: 3px solid #f43f5e !important; }
    .fi-wi-stats-overview-stat:nth-child(4) { border-top: 3px solid #8b5cf6 !important; }
    .fi-wi-stats-overview-stat:nth-child(5) { border-top: 3px solid #0ea5e9 !important; }
    .fi-wi-stats-overview-stat:nth-child(6) { border-top: 3px solid #f59e0b !important; }

    /* Valeur KPI — gros et lisible */
    .fi-wi-stats-overview-stat-value {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
        color: #0f172a !important;
    }
    .dark .fi-wi-stats-overview-stat-value {
        color: #f8fafc !important;
    }

    /* Label KPI */
    .fi-wi-stats-overview-stat-label {
        color: #475569 !important;
        font-weight: 600 !important;
        font-size: 0.8rem !important;
    }
    .dark .fi-wi-stats-overview-stat-label {
        color: #94a3b8 !important;
    }

    /* Description KPI (variation %, etc.) */
    .fi-wi-stats-overview-stat-description {
        font-weight: 600 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       TABLE
       ══════════════════════════════════════════════════════════════ */
    .fi-ta-table { font-size: 0.8125rem !important; }

    .fi-ta-row { transition: background 0.15s ease; }
    .fi-ta-row:hover {
        background: rgba(99,102,241,0.04) !important;
    }
    .dark .fi-ta-row:hover {
        background: rgba(99,102,241,0.08) !important;
    }

    /* En-têtes de colonnes */
    .fi-ta-header-cell {
        text-transform: uppercase !important;
        font-size: 0.6875rem !important;
        letter-spacing: 0.05em !important;
        color: #475569 !important;
        font-weight: 700 !important;
    }
    .dark .fi-ta-header-cell {
        color: #94a3b8 !important;
    }

    /* Cellules de tableau — texte lisible */
    .fi-ta-cell {
        color: #1e293b !important;
    }
    .dark .fi-ta-cell {
        color: #e2e8f0 !important;
    }

    /* Texte "Aucun résultat" */
    .fi-ta-empty-state-heading {
        color: #475569 !important;
        font-weight: 600 !important;
    }
    .dark .fi-ta-empty-state-heading {
        color: #94a3b8 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       BADGES
       ══════════════════════════════════════════════════════════════ */
    .fi-badge {
        font-weight: 700 !important;
        letter-spacing: 0.02em !important;
        border-radius: 0.5rem !important;
    }

    /* ══════════════════════════════════════════════════════════════
       FORMULAIRES
       ══════════════════════════════════════════════════════════════ */
    .fi-fo-field-wrp .fi-input,
    .fi-fo-field-wrp .fi-select {
        border-radius: 0.625rem !important;
        transition: var(--ach-transition);
    }
    .fi-fo-field-wrp .fi-input:focus-within {
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
        border-color: #6366f1 !important;
    }
    .dark .fi-fo-field-wrp .fi-input:focus-within {
        box-shadow: 0 0 0 3px rgba(129,140,248,0.2) !important;
        border-color: #818cf8 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       BOUTONS
       ══════════════════════════════════════════════════════════════ */
    .fi-btn {
        border-radius: 0.625rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em;
        transition: var(--ach-transition) !important;
    }
    .fi-btn:hover {
        transform: translateY(-1px);
    }

    /* ══════════════════════════════════════════════════════════════
       MODAL
       ══════════════════════════════════════════════════════════════ */
    .fi-modal-content {
        border-radius: var(--ach-radius) !important;
    }
    .fi-modal-heading {
        color: #0f172a !important;
        font-weight: 700 !important;
    }
    .dark .fi-modal-heading {
        color: #f1f5f9 !important;
    }
    .fi-modal-description {
        color: #475569 !important;
    }
    .dark .fi-modal-description {
        color: #cbd5e1 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       INFOLIST (page View)
       ══════════════════════════════════════════════════════════════ */
    .fi-in-entry-wrp-label {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .dark .fi-in-entry-wrp-label {
        color: #94a3b8 !important;
    }

    .fi-in-text {
        color: #0f172a !important;
        font-weight: 500 !important;
    }
    .dark .fi-in-text {
        color: #e2e8f0 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       NOTIFICATIONS
       ══════════════════════════════════════════════════════════════ */
    .fi-no {
        border-radius: 0.75rem !important;
    }
    .fi-no-title {
        font-weight: 700 !important;
    }

    /* ══════════════════════════════════════════════════════════════
       SCROLLBAR
       ══════════════════════════════════════════════════════════════ */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.2); }
    .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); }
    .dark ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

    /* ══════════════════════════════════════════════════════════════
       ANIMATIONS
       ══════════════════════════════════════════════════════════════ */
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fi-page > * { animation: fadeSlideUp 0.35s ease-out; }
    .fi-wi-stats-overview-stat { animation: fadeSlideUp 0.4s ease-out; }

    /* ══════════════════════════════════════════════════════════════
       BORDURES DOSSIER (lignes colorées dans la table)
       ══════════════════════════════════════════════════════════════ */
    .border-l-investissement { border-left: 4px solid #6366f1 !important; }
    .border-l-fonctionnement { border-left: 4px solid #10b981 !important; }
    .border-l-nopdf {
        border-left: 4px solid #f43f5e !important;
        background: rgba(244,63,94,0.03) !important;
    }
    .dark .border-l-nopdf {
        background: rgba(244,63,94,0.06) !important;
    }

    /* ══════════════════════════════════════════════════════════════
       PDF VIEWER
       ══════════════════════════════════════════════════════════════ */
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

    /* ══════════════════════════════════════════════════════════════
       CLOUD SETTINGS WIDGET (sidebar)
       ══════════════════════════════════════════════════════════════ */
    .ach-cloud-widget {
        margin: 8px;
        padding: 10px 12px;
        border-radius: 0.625rem;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.08);
        transition: var(--ach-transition);
    }
    .ach-cloud-widget:hover {
        background: rgba(255,255,255,0.1);
    }
    .ach-cloud-widget-title {
        color: #94a3b8;
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .ach-cloud-widget a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        color: #cbd5e1;
        font-size: 0.8rem;
        font-weight: 500;
        text-decoration: none;
        transition: var(--ach-transition);
    }
    .ach-cloud-widget a:hover {
        background: rgba(99,102,241,0.15);
        color: #e2e8f0;
    }
    .ach-cloud-widget a svg {
        width: 16px;
        height: 16px;
        opacity: 0.7;
    }
    .ach-cloud-widget a:hover svg {
        opacity: 1;
    }
</style>
</div>
