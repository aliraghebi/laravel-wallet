<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class Manager extends Model {
    use HasWallets;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];
}
