<?php

/*
 * Valores iniciales de la marca de cada instalación. `omni:install` los carga una sola vez en
 * "Ajustes de la agencia"; después la agencia los cambia desde el panel sin tocar archivos.
 */
return [
    'name' => env('BRAND_NAME', 'Mi Agencia'),
    'email' => env('BRAND_EMAIL', 'info@example.com'),
    'phone' => env('BRAND_PHONE', ''),
    'address' => env('BRAND_ADDRESS', ''),
    'website' => env('BRAND_WEBSITE', env('APP_URL')),

    'colors' => [
        'frontend_primary' => env('BRAND_COLOR_PRIMARY', '#1a56db'),
        'frontend_secondary' => env('BRAND_COLOR_SECONDARY', '#7e22ce'),
        'backend_primary' => env('BRAND_COLOR_PANEL', '#f59e0b'),
    ],

    'social' => [
        'instagram' => env('BRAND_INSTAGRAM_URL'),
        'facebook' => env('BRAND_FACEBOOK_URL'),
        'whatsapp' => env('BRAND_WHATSAPP_URL'),
    ],
];
