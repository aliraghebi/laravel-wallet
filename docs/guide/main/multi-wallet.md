# New Wallet

You can create an unlimited number of wallets, but the `slug` for each wallet should be unique.

## User Model

Add the `HasWallets` trait to model.

```php
use ArsamMe\Wallet\Traits\HasWallets;

class User extends Model
{
    use HasWallets;
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

## Default Wallet + MultiWallet

Is it possible to use the default wallet and multi-wallets at the same time? Yes.

```php
use ArsamMe\Wallet\Traits\HasWallets;

class User extends Model
{
    use HasWallet, HasWallets;
}
```

How to get the default wallet?

```php
$wallet = $user->wallet;
$wallet->balance; // 10
```

It is simple!
