<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Interfaces\Wallet;
use ArsamMe\Wallet\Interfaces\WalletFloat;
use ArsamMe\Wallet\Traits\HasWalletFloat;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class UserFloat extends Model, WalletFloat {
    use HasWalletFloat;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];

    public function getTable(): string {
        return 'users';
    }
}
