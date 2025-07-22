<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;

class WalletSumData extends BaseData {
    public function __construct(
        public readonly string $balance,
        public readonly string $frozenAmount,
        public readonly string $availableBalance,
    ) {}
}
