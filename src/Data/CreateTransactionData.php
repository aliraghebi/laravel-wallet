<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\BaseData;

class CreateTransactionData extends BaseData {
    public function __construct(
        public int|float|string $amount,
        public ?array $meta = null,
    ) {}
}
