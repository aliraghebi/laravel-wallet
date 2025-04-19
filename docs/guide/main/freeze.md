# Withdraw

Sometimes, we need to block an amount of wallet temporarily. In this case, we can use freeze method.

We can freeze total amount of wallet or a specific amount.

## Freeze Current Balance

Find user:

```php
$user = User::first(); 
```

As the user uses `HasWallets`, he will have `balance` and `available_balance` property.
Check the user's balance.

```php
$user->balance; // 100
$user->available_balance; // 100
```

Call Freeze method on the wallet.

```php
$user->freeze();
```

Get `balance` and `available_balance` properties.

```php
$user->balance; // 100
$user->available_balance; // 0
```

## Freeze Specific Amount

Find user:

```php
$user = User::first(); 
```

As the user uses `HasWallets`, he will have `balance` and `available_balance` property.
Check the user's balance.

```php
$user->balance; // 100
$user->available_balance; // 100
```

Call Freeze method on the wallet.

```php
$user->freeze(50);
```

Get `balance` and `available_balance` properties.

```php
$user->balance; // 100
$user->available_balance; // 50
```