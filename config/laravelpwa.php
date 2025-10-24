<?php

return [
    'name' => env('APP_NAME', 'Laravel'),

    'manifest' => [
        'name' => env('APP_NAME', 'Laravel'),
        'short_name' => env('APP_SHORT_NAME', env('APP_NAME', 'Laravel')),
        'description' => 'Gestión de pedidos y entregas',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'status_bar' => 'default',
        'background_color' => '#ffffff',
        'theme_color' => '#0d6efd',
        'lang' => 'es',
        'dir' => 'ltr',
        'icons' => [],
        'shortcuts' => [],
        'custom' => [],
    ],

    'service-worker' => [
        'src' => '/service-worker.js',
        'skip_waiting' => true,
    ],

    'offline' => [
        'route' => 'offline',
        'view' => 'offline',
    ],
];
