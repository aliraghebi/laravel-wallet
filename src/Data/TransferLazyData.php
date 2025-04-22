<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\Data\BaseData;
use ArsamMe\Wallet\Contracts\Models\Wallet;

class TransferLazyData extends BaseData {
    public function __construct(
        public readonly string $uuid,
        public readonly Wallet $fromWallet,
        public readonly Wallet $toWallet,
        public readonly string $amount,
        public readonly string $fee,
        public readonly int $decimalPlaces,
        public readonly TransactionData $withdrawData,
        public readonly TransactionData $depositData,
        public readonly ?array $meta
    ) {}
}
