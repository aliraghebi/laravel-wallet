# Deposit

A deposit is a sum of money which is part of the full price of something,
and which you pay when you agree to buy it.

In this case, the Deposit is the replenishment of the wallet.

## Make a Deposit

Find user:

```php
$user = User::first(); 
```

Since the user uses `HasWallet`, he will have `balance` property.
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

## Advanced Usage

In some cases, you may need to store additional information for a transaction, such as its purpose or a description. To
handle this, you can pass an object of type `TransactionExtra`.

```php
$extra = new TransactionExtra(
    uuid: '3cfe2a6c-9c43-4480-ba66-b3aff62c58b7',
    purpose: 'gift',
    description: 'Use won gift from campaign',
    meta: [
        'campaign_id' => 2589623
    ]
);

$user->deposit(100, $extra);
```