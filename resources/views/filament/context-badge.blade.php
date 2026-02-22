{{-- Widget sidebar — HTML pur, aucun JS, très léger --}}
<div style="margin:8px;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);">
    <div style="color:#810404;font-size:0.6rem;text-transform:uppercase;letter-spacing:0.1em;font-weight:600;margin-bottom:4px;padding-left:4px;">
        ⚡ Outils
    </div>

    {{-- Paramètres Cloud --}}
    <a href="{{ url('/admin/cloud-settings') }}"
       style="display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:6px;color:#cbd5e1;font-size:0.78rem;font-weight:500;text-decoration:none;"
       onmouseover="this.style.background='rgba(99,102,241,0.15)'"
       onmouseout="this.style.background='transparent'"
       title="Configurer la synchronisation cloud">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.7;flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
        </svg>
        Sync Cloud
    </a>

    {{-- Export & Backup --}}
    <a href="{{ url('/admin/backup/download') }}"
       style="display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:6px;color:#cbd5e1;font-size:0.78rem;font-weight:500;text-decoration:none;"
       onmouseover="this.style.background='rgba(99,102,241,0.15)'"
       onmouseout="this.style.background='transparent'"
       title="Exporter BDD + archives en ZIP">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.7;flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Export &amp; Backup
    </a>
</div>
