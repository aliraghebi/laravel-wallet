# Basic Usage

## Simple wallet transactions

The package is built on simple transactions:

- deposit: replenishment of the wallet;
- withdraw: withdrawal from the wallet;
- freeze: blocking an amount on the wallet preventing withdrawal;
- unFreeze: unblock the amount or total frozen amount on the wallet;

## How to use functions?

You can use functions in two ways:

- Using the `HasWallets` trait on your model (ex.: `User` model);
- Using the `Facade` class.

## Using Facade

You can use `LaravelWallet` facade to call functions directly without needing to add the `HasWallet` trait to your
model.

```php
// Creating a new wallet for our model (In this case, User)
$wallet = LaravelWallet::createWallet($user, new CreateWalletData(slug: 'my-wallet'));

// Or, Getting an already created wallet
$wallet = LaravelWallet::findOrFailBySlug($user, 'my-wallet');

// Deposit to the wallet
LaravelWallet::deposit($wallet, 1000);
```

## Using `HasWallet` trait

Add the `HasWallet` trait to model.

```php
use ArsamMe\Wallet\Traits\HasWallet;

class User extends Model
{
    use HasWallets;
}
```

Use functions directly on the model.

```php
$user->deposit(1000);
```

Consider an example:

```php
$user = User::first();
$user->balance; // 0

$user->deposit(10);
$user->balance; // 10

$user->withdraw(1, ['description' => 'payment of taxes']);
$user->balance; // 9
```

## How to work with fractional numbers?

This package will handle both integer and float numbers. You only need to set default `decimalPlaces` in the config file
or when creating wallet.

```php
// Creaing a new wallet with 6 decimal places
$wallet = LaravelWallet::createWallet($user, new CreateWalletData(decimalPlaces: 6));

// Depositing into wallet
$wallet->deposit(100.12345678);

// Getting the balance
$wallet->balance; // 100.123456
```