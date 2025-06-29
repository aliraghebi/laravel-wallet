# New Wallet

You can create an unlimited number of wallets, but the `slug` for each wallet should be unique.

## User Model

Add the `HasWallet` trait to model.

```php
use ArsamMe\Wallet\Traits\HasWallet;

class User extends Model
{
    use HasWallet;
}
```

## Create a wallet

Find user:

```php
$user = User::first(); 
```

Create a new wallet.

```php
$user->hasWallet('my-wallet'); // bool(false)
$wallet = $user->createWallet([
    'name' => 'New Wallet',
    'slug' => 'my-wallet',
]);

$user->hasWallet('my-wallet'); // bool(true)

$wallet->deposit(100);
$wallet->balance; // 100
```

## How to get the right wallet?

```php
$myWallet = $user->getWallet('my-wallet');
$myWallet->balance; // 100
```

## Sum Multiple Wallets

You can calculate the total balance across multiple wallets using any of these methods:

```php
$wallet = $user->wallet;

// Sum wallets by their IDs or Wallet models
$sum = LaravelWallet::sumWallets([1, 2, $wallet]);

// Sum wallets by their UUIDs
$sum = LaravelWallet::sumWalletsByUuid([
    'c97d8f76-b22a-42d2-b190-90a1c9f57671',
    '18b0cfb5-7fff-4205-9fd0-b8b682e9435e'
]);

// Sum wallets by slug or an array of slugs
$sum = LaravelWallet::sumWalletsBySlug('btc');

$sum->balance; // 1000.001
$sum->frozenAmount; // 0.000
$sum->availableAmount; // 1000.001
```

## Default Wallet + MultiWallet

Can you use the default wallet and multiple wallets simultaneously? Absolutely.

You can access the default wallet using the `wallet` attribute or the `getWallet()` method without providing a `slug`.

**⚠️ Using the `wallet` attribute will create the default wallet if it does not already exist.**

```php
// Get the default wallet using `wallet` attribute, Creates the wallet if it does not already exist.
$wallet = $user->wallet;

// Get the default wallet using `getWallet()` method, Returns null if it does not already exist.
$wallet = $user->getWallet();

// Get wallet balance
$wallet->balance; // 10
```

It is simple!
