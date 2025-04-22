<?php

namespace ArsamMe\Wallet\Test\Infra\Models;

use ArsamMe\Wallet\Interfaces\Customer;
use ArsamMe\Wallet\Interfaces\ProductLimitedInterface;
use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Services\CastService;
use ArsamMe\Wallet\Test\Infra\Exceptions\PriceNotSetException;
use ArsamMe\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $quantity
 * @property int $price
 * @property array<string, int> $prices
 *
 * @method int getKey()
 */
final class ItemMultiPrice extends Model implements ProductLimitedInterface {
    use HasWallet;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['name', 'quantity', 'price', 'prices'];

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

        if (array_key_exists($wallet->currency, $this->prices)) {
            return $this->prices[$wallet->currency];
        }

        throw new PriceNotSetException("Price not set for {$wallet->currency} currency");
    }

    public function getMetaProduct(): ?array {
        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'prices' => 'array',
        ];
    }
}
