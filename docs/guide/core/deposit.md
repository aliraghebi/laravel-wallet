# Deposit

A deposit is a sum of money which is part of the full price of something,
and which you pay when you agree to buy it.

In this case, the Deposit is the replenishment of the wallet.

## Make a Deposit

Find user:

```php
$user = User::first(); 
```

Since the user uses `HasWallets`, he will have `balance` property.
Let’s check the user's balance.

```php
$user->balance; // 0
```

The balance is now zero, as expected.
Put it on his 10 cents account.

```php
$user->deposit(10); 
$user->balance; // 10
```

Great! The balance is now 10 cents, the funds have been successfully added.
