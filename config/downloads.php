<?php

declare(strict_types=1);

return [
    'retries' => [
        'max'        => env('DL_MAX_RETRIES', 5)
    ],

    'paths' => [
        'tmp'       => storage_path('app/downloads/tmp'),
        'completed' => storage_path('app/downloads/completed'),
    ],

    'http' => [
        'dns_timeout'        => 10.0,
        'tcp_timeout'        => 10.0,
        'tls_timeout'        => 10.0,
        'read_write_timeout' => 30.0,
    ],
];
