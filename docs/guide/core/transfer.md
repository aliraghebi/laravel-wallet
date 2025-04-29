# Transfer between wallets

Transfer in our system are two well-known [Deposit](deposit) and [Withdraw](withdraw)
operations that are performed in one transaction.

The transfer takes place between wallets.

## User Model

Prepare the model, add the `HasWallet` trait.

```php
use ArsamMe\Wallet\Traits\HasWallet;

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
use ArsamMe\Wallet\Traits\HasWallet;
use ArsamMe\Wallet\Contracts\Models\Wallet;

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