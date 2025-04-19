# Withdraw

Unblocking the frozen amount of wallet can be done using `unFreeze` method.

## UnFreeze Current Balance

Find user:

```php
$user = User::first(); 
```

As the user uses `HasWallets`, he will have `balance` and `available_balance` property.
Check the user's balance.

```php
$user->balance; // 100
$user->available_balance; // 0
```

Call UnFreeze method on the wallet.

```php
$user->unFreeze();
```

Get `balance` and `available_balance` properties.

```php
$user->balance; // 100
$user->available_balance; // 100
```

## UnFreeze Specific Amount

Find user:

```php
$user = User::first(); 
```

As the user uses `HasWallets`, he will have `balance` and `available_balance` property.
Check the user's balance.

```php
$user->balance; // 100
$user->available_balance; // 0
```

Call UnFreeze method on the wallet.

```php
$user->unFreeze(50);
```

Get `balance` and `available_balance` properties.

```php
$user->balance; // 100
$user->available_balance; // 50
```