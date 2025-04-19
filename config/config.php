<?php

use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Wallet;

return [
    /*
    |--------------------------------------------------------------------------
    |  Wallet consistency check settings
    |--------------------------------------------------------------------------
    |
    | Consistency is verified using checksum, in case of checksum mismatch, transaction will be failed and rolled back.
    */
    'consistency' => [
        /*
        | Weather to enable consistency check.
        */
        'enabled' => env('WALLET_CONSISTENCY_CHECK_ENABLED', true),
        /*
        | Secret key for checksum generation.
        */
        'secret' => env('WALLET_CONSISTENCY_SECRET', 'consistency_secret'),
    ],

    /*
    |--------------------------------------------------------------------------
    |  Base model 'wallet'.
    |--------------------------------------------------------------------------
    |
    | Contains the configuration for the wallet model.
    */
    'wallet' => [
        /*
        | The table name for wallets.
        |
        | This value is used to store wallets in a database.
        */
        'table' => env('WALLET_WALLET_TABLE_NAME', 'wallets'),

        /*
        | The model class for wallets.
        |
        | This value is used to create new wallets.
        */
        'model' => Wallet::class,

        /*
        | The default configuration for wallets.
        */
        'default' => [
            /*
            | The name of the default wallet.
            */
            'name' => env('WALLET_DEFAULT_WALLET_NAME', 'Default Wallet'),

            /*
            | The slug of the default wallet.
            */
            'slug' => env('WALLET_DEFAULT_WALLET_SLUG', 'default'),

            /*
            | The meta information of the default wallet.
            */
            'meta' => [],

            /*
            | Default decimal places for new wallets if you do not set
            */
            'decimal_places' => env('WALLET_DEFAULT_WALLET_DECIMAL_PLACES', 24),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    |  Base model 'transaction'.
    |--------------------------------------------------------------------------
    */
    'transaction' => [
        /*
        | The table name for transactions.
        |
        | This value is used to store transactions in a database.
        */
        'table' => env('WALLET_TRANSACTION_TABLE_NAME', 'transactions'),

        /*
        | The model class for transactions.
        |
        | This value is used to create new transactions.
        */
        'model' => Transaction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    |  Storage of the state of the balance of wallets.
    |--------------------------------------------------------------------------
    |
    | This is used to cache the results of calculations
    | in order to improve the performance of the package.
    |
    */
    'cache' => [
        /*
        | The driver for the cache.
        */
        'driver' => env('WALLET_CACHE_DRIVER', 'array'),
        /*
        | The time to live for the cache in seconds.
        */
        'ttl' => env('WALLET_CACHE_TTL', 24 * 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    |  A system for dealing with race conditions.
    |--------------------------------------------------------------------------
    |
    | This is used to protect against race conditions
    | when updating the balance of a wallet.
    |
    */
    'lock' => [
        /*
        | The driver for the lock.
        |
        | The following drivers are supported:
        | - array
        | - redis
        | - memcached
        | - database
        */
        'driver' => env('WALLET_LOCK_DRIVER', 'array'),

        /*
        | The time to live for the lock in seconds.
        */
        'seconds' => env('WALLET_LOCK_TTL', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    |  Arbitrary Precision Calculator.
    |--------------------------------------------------------------------------
    |
    | The 'scale' option defines the number of decimal places
    | that the calculator will use when performing calculations.
    |
    */
    'math' => [
        /*
        | The scale of the calculator.
        */
        'scale' => env('WALLET_MATH_SCALE',64),
    ],
];
