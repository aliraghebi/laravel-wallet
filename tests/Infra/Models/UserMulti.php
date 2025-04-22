<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Interfaces\Wallet;
use ArsamMe\Wallet\Interfaces\WalletFloat;
use ArsamMe\Wallet\Traits\HasWalletFloat;
use ArsamMe\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class UserMulti extends Model implements Wallet, WalletFloat {
    use HasWalletFloat;
    use HasWallets;

    public function getTable(): string {
        return 'users';
    }
}
