<?php
return [
    'name'     => env('APP_NAME', 'El Sazón de la Abuela'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => env('APP_DEBUG', 'false') === 'true',
    'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
    'session_idle_seconds' => (int) env('SESSION_IDLE_SECONDS', '1800'),
    'fundacion' => 2009,
    'por_pagina' => 10,
    'impoconsumo' => 0.08,
];
