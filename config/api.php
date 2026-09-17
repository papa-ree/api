<?php

return [
    'throttle' => [
        'per_minute' => (int) env('API_THROTTLE_PER_MINUTE', 60),
    ],

    'token' => [
        'prefix' => env('API_TOKEN_PREFIX', 'rkc_'),
        'hash_algo' => env('API_TOKEN_HASH_ALGO', 'sha256'),
        'prune_days' => (int) env('API_TOKEN_PRUNE_DAYS', 30),
    ],

    'harden' => [
        'enabled' => (bool) env('API_HARDEN_ENABLED', true),
        'deny_message' => env('API_HARDEN_DENY_MESSAGE', 'Forbidden.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope API
    |--------------------------------------------------------------------------
    |
    | Scope didaftarkan oleh masing-masing package lewat helper
    | registerApiScopes() di ServiceProvider package. Lihat ApiScopeRegistry.
    |
    */
];
