<?php

use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet;

return [
    /*
    |--------------------------------------------------------------------------
    |  Number digits and decimal places used in migrations and calculations
    |--------------------------------------------------------------------------
    */
    'number' => [
        /*
        | Total number of digits used to store numbers in the database, including both integer and fractional parts.
        */
        'digits' => 64,

        /*
        | Number of decimal digits used for storing and calculating values.
        | All numeric values will be scaled to the specified `decimal_places` to ensure data integrity and consistency.
        */
        'decimal_places' => env('WALLET_DECIMAL_PLACES', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    |  Wallet integrity check settings
    |--------------------------------------------------------------------------
    |
    | Integrity is verified using checksum, in case of checksum mismatch, transaction will be failed and rolled back.
    */
    'integrity_validation' => [
        /*
        | Weather to enable integrity check. If you disable this option, no validation will be done on wallets on update.
        */
        'enabled' => env('WALLET_INTEGRITY_VALIDATION_ENABLED', true),
        /*
        | Secret key for checksum generation.
        */
        'secret' => env('WALLET_INTEGRITY_VALIDATION_SECRET', 'integrity_validation_secret'),
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
            'meta' => null,
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
   |  Base model 'transfer'.
   |--------------------------------------------------------------------------
   */
    'transfer' => [
        /*
        | The table name for transfers.
        |
        | This value is used to store transfers in a database.
        */
        'table' => env('WALLET_TRANSFER_TABLE_NAME', 'transfers'),

        /*
        | The model class for transfer.
        |
        | This value is used to create new transfer.
        */
        'model' => Transfer::class,
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
];
