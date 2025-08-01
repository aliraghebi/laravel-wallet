# Asynchronous wallet creation

The idea is based on the division into teams for creating wallets, transactions, etc. The creation of a wallet can be
accelerated if the client "generates a wallet himself".

## User Model

Add the `HasWallet` trait to model.

```php
use AliRaghebi\Wallet\Traits\HasWallet;

class User extends Model
{
    use HasWallet;
}
```

## Action Handler

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFactory;
...

public function __invoke(User $user, Request $request): Response
{
    $name = $request->get('wallet_name');
    $uuid = $request->get('wallet_uuid');

    $message = new CreateWalletCommandMessage($user, $name, $uuid);
    dispatch($message);

    return ResponseFactory::json([], 202);
}
```

## Command Handler

```php
public function __invoke(CreateWalletCommandMessage $message): void
{
    $user = $message->getUser();
    $user->createWallet([
        'uuid' => $message->getWalletUuid(),
        'name' => $message->getWalletName(),
    ]);
}
```

You receive requests to create a wallet on the backend, and you create them asynchronously. UUID is generated on the
client side and the client already knows it. You will not be able to create two wallets with one uuid, because the
column in the database is unique.

The user no longer needs to wait for the creation of a wallet, it is enough to know the uuid. You get the most stable
application.

---
It is simple!
