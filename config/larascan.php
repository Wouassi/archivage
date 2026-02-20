<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Larascan — Intégration Scanner.js (Asprise)
    |--------------------------------------------------------------------------
    | Basé sur https://github.com/hermezdelay/larascan
    | Scanner.js détecte automatiquement les scanners TWAIN/WIA/SANE
    | connectés au poste client et permet le scan direct depuis le navigateur.
    */

    'enabled' => env('LARASCAN_ENABLED', true),

    // URL du CDN Scanner.js (ou chemin local si hébergé)
    'scanner_js_url' => env('LARASCAN_JS_URL', 'https://cdn.asprise.com/scannerjs/scanner.js'),

    // Route de réception des scans
    'upload_url' => '/scan/upload',

    // Paramètres de scan par défaut
    'default_dpi' => env('LARASCAN_DPI', 200),
    'default_color' => env('LARASCAN_COLOR', 'RGB'),      // RGB, GRAY, BW
    'default_format' => env('LARASCAN_FORMAT', 'pdf'),     // jpg, pdf, png
    'default_paper' => env('LARASCAN_PAPER', 'TWSS_A4'),   // TWSS_A4, TWSS_USLETTER

    // Utiliser le dialogue Asprise (prévisualisation, réorganisation)
    'use_asprise_dialog' => env('LARASCAN_DIALOG', true),

    // Montrer l'UI natif du scanner
    'show_scanner_ui' => env('LARASCAN_NATIVE_UI', false),

    // Compression PDF
    'pdf_compression' => env('LARASCAN_PDF_COMPRESSION', true),
];
