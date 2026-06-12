<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(
        explode(',', env('CORS_ALLOWED_ORIGINS', env('APP_FRONTEND_URL', 'http://localhost:3000')))
    ),

    // Permite requisições de apps desktop (Tauri/Electron) que enviam Origin: null
    // e de qualquer esquema tauri:// ou capacitor://
    'allowed_origins_patterns' => [
        '#^null$#',
        '#^tauri://#',
        '#^capacitor://#',
    ],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-Correlation-ID'],

    'exposed_headers' => ['X-Correlation-ID'],

    'max_age' => 86400,

    'supports_credentials' => false,

];
