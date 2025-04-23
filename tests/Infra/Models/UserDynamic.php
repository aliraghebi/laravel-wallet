<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $email
 *
 * @method int getKey()
 */
final class UserDynamic extends Model {
    use HasWallet;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email'];

    public function getTable(): string {
        return 'users';
    }

    /**
     * @return non-empty-string
     */
    public function getDynamicDefaultSlug(): string {
        return 'default-'.$this->email;
    }
}
