<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Models\Transfer;

interface TransferServiceInterface {
    public function transfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?TransferExtra $extra = null): Transfer;
}
