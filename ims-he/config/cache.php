<?php

return [

    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),

    'stores' => [
        // phpunit.xml sets CACHE_STORE=array so tests never share rate-limit counters between runs.
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'laravel-cache'),

];
