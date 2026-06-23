<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Permite que el Frontend en Vite (http://localhost:3000, ver
    | front/vite.config.mjs) consuma la API de Laravel servida en
    | http://localhost:8000. La autenticación usa Bearer tokens de
    | Sanctum (no cookies), por lo que supports_credentials permanece
    | en false.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
