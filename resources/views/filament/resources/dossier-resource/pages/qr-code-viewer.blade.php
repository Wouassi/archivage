@php
    $qrUrl = null;
    if (class_exists(\App\Services\QrCodeService::class) && $getRecord()) {
        $qrUrl = \App\Services\QrCodeService::getQrUrl($getRecord());
    }
@endphp

@if($qrUrl)
<div style="text-align:center;padding:16px;">
    <img src="{{ $qrUrl }}" alt="QR Code Dossier" style="width:200px;height:200px;border-radius:8px;border:2px solid #e2e8f0;">
    <p style="margin-top:8px;font-size:0.75rem;color:#64748b;">
        Scannez ce QR Code pour accéder au dossier <strong>{{ $getRecord()->ordre_paiement }}</strong>
    </p>
</div>
@else
<p style="text-align:center;color:#94a3b8;font-size:0.82rem;padding:12px;">
    QR Code non disponible.
</p>
@endif
