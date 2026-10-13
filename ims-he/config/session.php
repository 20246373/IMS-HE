<?php

return [

    'driver' => 'file',

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    'files' => storage_path('framework/sessions'),

    'cookie' => env('SESSION_COOKIE_NAME', 'laravel_session'),

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => true,

    'same_site' => 'lax',

];
