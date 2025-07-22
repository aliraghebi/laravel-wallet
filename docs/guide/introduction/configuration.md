# Configuration

Though this package is crafted to suit most of your needs by default, you can edit the configuration file to suit
certain demands.

## Environment

| Name                                   | Description                          | Default            | 
|----------------------------------------|--------------------------------------|--------------------|
| `WALLET_CONSISTENCY_CHECK_ENABLED`     | Weather to enable consistency check. | true               |
| `WALLET_CONSISTENCY_SECRET`            | Secret key for checksum generation.  | consistency_secret |
| `WALLET_WALLET_TABLE_NAME`             | Wallet table name                    | wallets            |
| `WALLET_DEFAULT_WALLET_NAME`           | Default wallet name                  | Default Wallet     |
| `WALLET_DEFAULT_WALLET_SLUG`           | Default wallet slug                  | default            |
| `WALLET_DEFAULT_WALLET_DECIMAL_PLACES` | Default wallet decimal places        | 24                 |
| `WALLET_TRANSACTION_TABLE_NAME`        | Transaction table name               | transactions       |
| `WALLET_CACHE_DRIVER`                  | Cache for wallet balance             | array              |
| `WALLET_CACHE_TTL`                     | Cache TTL for wallet balance         | 24h                |
| `WALLET_LOCK_DRIVER`                   | Lock for wallets                     | array              |
| `WALLET_LOCK_TTL`                      | Lock TTL for wallets                 | 1s                 |
| `WALLET_MATH_SCALE`                    | Select mathematical precision        | 64                 |

## Configure default wallet

Customize `name`,`slug`, `meta` and `decimalPlaces` of default wallet.

config/wallet.php:

```php
'default' => [
    'name' => 'Ethereum',
    'slug' => 'ETH',
    'meta' => [],
    'decimal_places' => 18,
],
```

## Extend base Wallet model

You can extend base Wallet model by creating a new class that extends `AliRaghebi\Wallet\Models\Wallet` and registering the
new class in `config/wallet.php`.
Example `MyWallet.php`

App/Models/MyWallet.php:

```php
use AliRaghebi\Wallet\Models\Wallet as WalletBase;

class MyWallet extends WalletBase {
    public function helloWorld(): string { return "hello world"; }
}
```

### Register base Wallet model

config/wallet.php:

```php
'wallet' => [
    'table' => 'wallets',
    'model' => MyWallet::class,
    'default' => [
        'name' => 'Default Wallet',
        'slug' => 'default',
        'meta' => [],
    ],
],
```

```php
echo $user->wallet->helloWorld();
```

This same method above, can be used to extend the base `Transaction` model and registering the extended
model in the configuration file.

