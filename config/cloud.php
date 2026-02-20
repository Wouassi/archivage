<?php

return [
    'provider' => env('CLOUD_PROVIDER', 'disabled'),
    'auto_sync' => env('CLOUD_AUTO_SYNC', false),
    'root_folder' => env('CLOUD_ROOT_FOLDER', 'ArchivageComptable'),
    'keep_structure' => env('CLOUD_KEEP_STRUCTURE', true),

    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    ],
    'dropbox' => [
        'access_token' => env('DROPBOX_ACCESS_TOKEN'),
        'app_key' => env('DROPBOX_APP_KEY'),
        'app_secret' => env('DROPBOX_APP_SECRET'),
    ],
    's3' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'eu-west-3'),
        'bucket' => env('AWS_BUCKET'),
    ],
];
