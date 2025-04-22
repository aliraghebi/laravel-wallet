<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Interfaces\Customer;
use ArsamMe\Wallet\Interfaces\MinimalTaxable;
use ArsamMe\Wallet\Interfaces\ProductLimitedInterface;
use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Services\CastService;
use ArsamMe\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $quantity
 * @property int $price
 *
 * @method int getKey()
 */
final class ItemMinTax extends Model implements MinimalTaxable, ProductLimitedInterface {
    use HasWallet;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'quantity', 'price'];

    public function getTable(): string {
        return 'items';
    }

    public function canBuy(Customer $customer, int $quantity = 1, bool $force = false): bool {
        $result = $this->quantity >= $quantity;

        if ($force) {
            return $result;
        }

        return $result && !$customer->paid($this) instanceof \ArsamMe\Wallet\Models\Transfer;
    }

    public function getAmountProduct(Customer $customer): int {
        /** @var Wallet $wallet */
        $wallet = app(CastService::class)->getWallet($customer);

        return $this->price + (int) $wallet->holder_id;
    }

    public function getMetaProduct(): ?array {
        return null;
    }

    public function getFeePercent(): float {
        return 3;
    }

    public function getMinimalFee(): int {
        return 90;
    }
}
