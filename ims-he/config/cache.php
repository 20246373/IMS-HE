<?php

return [

    // CACHE_STORE is the setting .env and phpunit.xml use; CACHE_DRIVER is still read for older .env files.
    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'laravel-cache'),

];
