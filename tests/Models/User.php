<?php

namespace AliRaghebi\Wallet\Test\Models;

use AliRaghebi\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class User extends Model implements \AliRaghebi\Wallet\Contracts\Models\Wallet {
    use HasWallet;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];
}
