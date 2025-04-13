<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\BaseData;

class WalletData extends BaseData
{
    public function __construct(
        public string $uuid,
        public string $balance,
        public string $frozenAmount,
        public string $totalCredit,
        public string $totalDebit,
        public string $checksum,
    )
    {
    }
}