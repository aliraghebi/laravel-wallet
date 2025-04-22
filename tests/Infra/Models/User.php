<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class User extends Model implements Wallet {
    use HasWallets;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];
}
