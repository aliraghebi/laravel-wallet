<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Interfaces\Customer;
use ArsamMe\Wallet\Interfaces\ProductLimitedInterface;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Services\CastService;
use ArsamMe\Wallet\Services\CastServiceInterface;
use ArsamMe\Wallet\Test\Infra\Helpers\Config;
use ArsamMe\Wallet\Traits\HasWallet;
use ArsamMe\Wallet\Traits\HasWallets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property int $quantity
 * @property int $price
 *
 * @method int getKey()
 */
final class Item extends Model implements ProductLimitedInterface {
    use HasWallet;
    use HasWallets;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'quantity', 'price'];

    public function canBuy(Customer $customer, int $quantity = 1, bool $force = false): bool {
        $result = $this->quantity >= $quantity;

        if ($force) {
            return $result;
        }

        return $result && !$customer->paid($this) instanceof Transfer;
    }

    public function getAmountProduct(Customer $customer): int {
        /** @var Wallet $wallet */
        $wallet = app(CastService::class)->getWallet($customer);

        return $this->price + (int) $wallet->holder_id;
    }

    public function getMetaProduct(): ?array {
        return null;
    }

    /**
     * @param  int[]  $walletIds
     * @return HasMany<Transfer>
     */
    public function boughtGoods(array $walletIds): HasMany {
        return app(CastServiceInterface::class)
            ->getWallet($this)
            ->hasMany(Config::classString('wallet.transfer.model', Transfer::class), 'to_id')
            ->where('status', Transfer::STATUS_PAID)
            ->whereIn('from_id', $walletIds);
    }
}
