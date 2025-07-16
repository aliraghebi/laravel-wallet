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

## Advanced Usage

In some cases, you may need to store additional information for a transaction, such as its purpose or a description. To
handle this, you can pass an object of type `TransactionExtra`.

- Passing a `uuid` helps prevent creating duplicate transactions, as it must be unique.
- The `purpose` field is a string (up to 48 characters) that is indexed in the database, allowing you to efficiently
  query transactions by their purpose.

```php
$extra = new TransactionExtra(
    uuid: '7eaa8494-0ce3-4640-819d-8934be5a9c05',
    purpose: 'order', // maximum 48 chars
    description: 'User bought iPhone 16 with invoice number #15024254',
    meta: [
        'product_id' => 10124,
        'product_variant_id' => 2141534,
    ]
);

$user->withdraw(999, $extra);
```

## And what will happen if the money is not enough?

There can be two situations:

- The user's balance is zero, then we get an error
  `ArsamMe\Wallet\Exceptions\BalanceIsEmptyException`
- If the balance is greater than zero, but it is not enough
  `ArsamMe\Wallet\Exceptions\InsufficientFundsException`
