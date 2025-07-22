<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;

class WalletStateData extends BaseData {
    public function __construct(
        public string $balance,
        public string $frozenAmount,
        public int $transactionsCount
    ) {}
}
