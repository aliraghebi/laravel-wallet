<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Interfaces\Confirmable;
use ArsamMe\Wallet\Interfaces\Wallet;
use ArsamMe\Wallet\Traits\CanConfirm;
use ArsamMe\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class UserConfirm extends Model implements Confirmable, Wallet {
    use CanConfirm;
    use HasWallet;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];

    public function getTable(): string {
        return 'users';
    }
}
