<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;
use AliRaghebi\Wallet\Contracts\Models\Wallet;

class TransferLazyData extends BaseData {
    public function __construct(
        public readonly string $uuid,
        public readonly Wallet $fromWallet,
        public readonly Wallet $toWallet,
        public readonly string $amount,
        public readonly string $fee,
        public readonly TransactionData $withdrawalData,
        public readonly TransactionData $depositData,
        public readonly ?string $purpose,
        public readonly ?string $description,
        public readonly ?array $meta
    ) {}
}
