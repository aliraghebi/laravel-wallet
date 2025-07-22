<?php

use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet;

return [
    /*
    |--------------------------------------------------------------------------
    |  Numbers type and decimal count configuration
    |--------------------------------------------------------------------------
    |
    | Control how numbers are saved in database.
    */
    'number' => [
        /*
        | Specifies the database column type for storing numbers. Changing this setting after migrations and with existing
        | data may cause data integrity issues.
        |
        | Supported Types are:
        | - unscaled
        |   Stores decimal values as unscaled integers. For example: 109.213 is stored as 109213.
        |   When using this type, `digits` defines the length of the decimal column, and `decimal_places` is ignored
        |   during migrations.
        |   Note: `decimal_places` is only used as the default for `$decimalPlaces` when creating wallets.
        |   Defining `$decimalPlaces` when creating a wallet is required; if not provided, the default value is used.
        |
        | - decimal
        |   Stores decimal values directly in the database without conversion or scaling.
        |   When using this type, both `digits` and `decimal_places` are applied during migrations.
        |
        | - big_integer
        | - integer
        |   For these types, `digits` and `decimal_places` are ignored. Numbers are stored as integers in the database
        |   without modification.
        */
        'type' => 'unscaled',

        /*
        | Specifies the total number of digits for numbers stored as `decimal` or `unscaled` types, including both integer and
        | fractional parts.
        */
        'digits' => 64,

        /*
        | Number of decimal places for `decimal` and `unscaled` types. Ignored for `big_integer` and `integer`.
        | For `decimal`, defines the fractional digits in database tables.
        | For `unscaled`, sets the default `$decimalPlaces` when creating a wallet if not specified.
        */
        'decimal_places' => 0,
    ],

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
