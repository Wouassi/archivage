<?php
return [
    'enabled' => env('SCANNER_ENABLED', true),
    'service_url' => env('SCANNER_SERVICE_URL', 'http://localhost:7780'),
    'naps2_path' => env('NAPS2_PATH', 'C:\\NAPS2\\NAPS2.Console.exe'),
    'default_dpi' => env('SCANNER_DPI', 200),
    'default_source' => env('SCANNER_SOURCE', 'auto'),
    'default_color' => env('SCANNER_COLOR', 'color'),
];
