<?php

namespace ArsamMe\Wallet\Test\Models;

use ArsamMe\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class User extends Model implements \ArsamMe\Wallet\Contracts\Models\Wallet {
    use HasWallet;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];
}
