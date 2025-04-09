<?php

return [
    'wallet' => [
        'table' => 'wallets',
        'creating' => [
            'decimal_places' => 24
        ]
    ],

    'transaction' => [
        'table' => 'transactions',
    ],

    'lock' => [
        'seconds' => 1
    ],

    'math' => [
        'scale' => 24,
    ],
];
