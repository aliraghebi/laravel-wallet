<?php

return [
    'database' => [
        'connection' => 'sqlite',
    ],

    'consistency' => [
        'secret' => '',
    ],

    'wallet' => [
        'table'    => 'wallets',
        'model'    => \ArsamMe\Wallet\Models\Wallet::class,
        'creating' => [
            'decimal_places' => 24,
        ],
    ],

    'transaction' => [
        'table' => 'transactions',
        'model' => \ArsamMe\Wallet\Models\Transaction::class,
    ],

    'cache' => [
        'driver' => env('WALLET_CACHE_DRIVER', 'array'),
        'ttl'    => env('WALLET_CACHE_TTL', 24 * 3600),
    ],

    'lock' => [
        'seconds' => 1,
    ],

    'math' => [
        'scale' => 64,
    ],
];
