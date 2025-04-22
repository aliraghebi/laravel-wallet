<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\Data\BaseData;

class WalletStateData extends BaseData {
    public function __construct(
        public string $balance,
        public string $frozenAmount,
        public int $transactionsCount
    ) {}
}
