<?php

namespace ArsamMe\Wallet\Test\Infra\PackageModels;

use ArsamMe\Wallet\Test\Infra\Values\Money;

/**
 * Class Transaction.
 *
 * @property Money $currency
 */
final class TransactionMoney extends \ArsamMe\Wallet\Models\Transaction {
    private ?Money $currency = null;

    public function getCurrencyAttribute(): Money {
        $this->currency ??= new Money($this->amount, $this->meta['currency'] ?? 'USD');

        return $this->currency;
    }
}
