It is necessary to expand the model that will have the wallet.
This is done in two stages:
  - Add `Wallet` interface;
  - Add the `HasWallet` trait;

Let's get started.
```php
use ArsamMe\Wallet\Traits\HasWallet;

class User extends Model
{
    use HasWallets;
}
```

The model is prepared to work with a wallet.
