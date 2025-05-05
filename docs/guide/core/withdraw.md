# Withdraw

When there is enough money in the account, you can withdraw it or buy something in the system.

Since the currency is virtual, you can buy any services on your website.
For example, priority in search results.

## Make a Withdraw

Find user:

```php
$user = User::first(); 
```

Since the user uses `HasWallet`, he will have `balance` property.
Let’s check the user's balance.

```php
$user->balance; // 100
```

The balance is now not empty, so you can withdraw funds.

```php
$user->withdraw(10); 
$user->balance; // 90
```

It is simple!

## And what will happen if the money is not enough?

There can be two situations:

- The user's balance is zero, then we get an error
  `ArsamMe\Wallet\Exceptions\BalanceIsEmptyException`
- If the balance is greater than zero, but it is not enough
  `ArsamMe\Wallet\Exceptions\InsufficientFundsException`
