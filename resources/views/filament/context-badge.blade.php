{{-- Widget sidebar — léger, HTML pur --}}

{{-- ═══ Bannière mise à jour (si version récente) ═══ --}}
@php
    $vFile = storage_path('app/app_version.json');
    $showBanner = false;
    $ver = '1.0.0';
    if (file_exists($vFile)) {
        $vData = json_decode(file_get_contents($vFile), true);
        $ver = $vData['version'] ?? '1.0.0';
        $updatedAt = $vData['updated_at'] ?? null;
        // Afficher la bannière pendant 7 jours après une mise à jour
        if ($updatedAt && \Carbon\Carbon::parse($updatedAt)->diffInDays(now()) < 7) {
            $showBanner = true;
        }
    }
@endphp

@if($showBanner)
<div class="ach-update-banner">
    🚀 v{{ $ver }}
    <a href="{{ url('/admin') }}" onclick="this.parentElement.style.display='none'">OK</a>
</div>
@endif

{{-- ═══ Liens outils ═══ --}}
<div style="margin:8px;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);">
    <div style="color:#94a3b8;font-size:0.58rem;text-transform:uppercase;letter-spacing:0.12em;font-weight:600;margin-bottom:4px;padding-left:4px;">
        ⚡ Outils
    </div>
    <a href="{{ url('/admin/cloud-settings') }}"
       style="display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:6px;color:#ffffff;font-size:0.78rem;font-weight:500;text-decoration:none;"
       onmouseover="this.style.background='rgba(99,102,241,0.15)'"
       onmouseout="this.style.background='transparent'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:.7;flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
        </svg>
        Sync Cloud
    </a>
    <a href="{{ url('/admin/backup/download') }}"
       style="display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:6px;color:#ffffff;font-size:0.78rem;font-weight:500;text-decoration:none;"
       onmouseover="this.style.background='rgba(99,102,241,0.15)'"
       onmouseout="this.style.background='transparent'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:.7;flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Export &amp; Backup
    </a>
    <a href="{{ url('/admin/roles-utilisateurs') }}"
       style="display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:6px;color:#ffffff;font-size:0.78rem;font-weight:500;text-decoration:none;"
       onmouseover="this.style.background='rgba(99,102,241,0.15)'"
       onmouseout="this.style.background='transparent'">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:.7;flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        Rôles utilisateurs
    </a>
    {{-- Version discrète --}}
    <div style="color:#e73111;font-size:0.55rem;text-align:center;margin-top:6px;opacity:0.6;">
        v{{ $ver }}
    </div>
</div>
