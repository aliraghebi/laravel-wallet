<?php

return [
    'secret' => '',
    'database' => [
        'connection' => 'sqlite',
    ],

    'wallet' => [
        'table' => 'wallets',
        'model' => \ArsamMe\Wallet\Models\Wallet::class,
        'creating' => [
            'decimal_places' => 24,
        ],
    ],

    'transaction' => [
        'table' => 'transactions',
        'model' => \ArsamMe\Wallet\Models\Transaction::class
    ],

    'lock' => [
        'seconds' => 1,
    ],

    'math' => [
        'scale' => 24,
    ],
];
