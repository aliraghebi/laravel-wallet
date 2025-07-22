# Transfer between wallets

Transfer in our system are two well-known [Deposit](deposit) and [Withdraw](withdraw)
operations that are performed in one transaction.

The transfer takes place between wallets.

## User Model

Prepare the model, add the `HasWallet` trait.

```php
use AliRaghebi\Wallet\Traits\HasWallet;

class User extends Model
{
    use HasWallet;
}
```

## Make a Transfer

Find user:

```php
$first = User::first(); 
$last = User::orderBy('id', 'desc')->first(); // last user
$first->getKey() !== $last->getKey(); // true
```

Create new wallets for users.

```php
$firstWallet = $first->createWallet(['name' => 'First User Wallet']);
$lastWallet = $last->createWallet(['name' => 'Second User Wallet']);

$firstWallet->deposit(100);
$firstWallet->balance; // 100
$lastWallet->balance; // 0
```

The transfer will be from the first user to the last.

```php
$firstWallet->transfer($lastWallet, 5); 
$firstWallet->balance; // 95
$lastWallet->balance; // 5
```

You can apply a fee when transferring funds.

```php
$firstWallet->transfer($lastWallet, 5, fee: 2); 
$firstWallet->balance; // 95
$lastWallet->balance; // 3
```

## Transferring to default wallet

Implementing `Wallet` interface in your model allows you to transfer funds to the default wallet.
This way you can pass `$user` as a parameter to the `transfer` method.

```php
use AliRaghebi\Wallet\Traits\HasWallet;
use AliRaghebi\Wallet\Contracts\Models\Wallet;

class User extends Model implements Wallet
{
    use HasWallet;
}
```

```php
$user1 = User::first();
$user2 = User::orderBy('id', 'desc')->first(); // last user

$user1->transfer($user2, 10, fee: 5);
```

It's simple!

## Advanced Usage

In some cases, you may need to store additional information for a transfer, such as its purpose or a description. To
handle this, you can pass an object of type `TransferExtra`.

- Passing a `uuid` helps prevent creating duplicate transfers, as it must be unique.
- The `purpose` field is a string (up to 48 characters) that is indexed in the database, allowing you to efficiently
  query transfers by their purpose.

```php
$depositExtra = new TransactionExtra(...);
$withdrawalExtra = new TransactionExtra(...);

$extra = new TransferExtra(
    uuid: 'eef32865-9835-45b7-909b-ea41c4bf760c',
    purpose: 'internal_transferred', // maximum 48 chars
    description: 'Internal transfer between users',
    meta: [
        'transfer_feature_status' => 'some_status',
    ],
    depositExtra: $depositExtra, 
    withdrawalExtra: $withdrawalExtra,
);

$user1->transfer($user2, 10, fee: 5, extra: $extra);
```