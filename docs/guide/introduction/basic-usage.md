# Basic Usage

## Simple wallet transactions

The package is built on simple transactions:

- deposit: replenishment of the wallet;
- withdraw: withdrawal from the wallet;
- freeze: blocking an amount on the wallet preventing withdrawal;
- unFreeze: unblock the amount or total frozen amount on the wallet;

## How to use functions?

You can use functions in two ways:

- Using the `HasWallet` trait on your model (ex.: `User` model);
- Using the `Facade` class.

## Using Facade

You can use `LaravelWallet` facade to call functions directly without needing to add the `HasWallet` trait to your
model.

```php
// Creating a new wallet for our model (In this case, User)
$wallet = LaravelWallet::createWallet($user, slug: 'my-wallet');

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
    use HasWallet;
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

This package supports both integer and float numbers. You can set the default `decimal_places` in the config file or
`decimalPlaces` when
creating a wallet. All amounts are rounded to the specified decimal places and returned as strings to avoid PHP's
float precision limitation. Additionally, attributes are available to retrieve amounts as floats or integers.

```php
// Creaing a new wallet with 6 decimal places
$wallet = LaravelWallet::createWallet($user, decimalPlaces: 6);

// Depositing into wallet
$wallet->deposit(100.12345678);

// Getting the balance
$wallet->balance; // '100.123456'

// Getting int balance of wallet
$wallet->balance_int; // 100

// Getting float balance of wallet
$wallet->balance_float; // 100.123456
```