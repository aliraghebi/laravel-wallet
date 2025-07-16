<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\Data\BaseData;

class TransactionExtra extends BaseData {
    public function __construct(
        public readonly ?string $uuid = null,
        public readonly ?string $purpose = null,
        public readonly ?string $description = null,
        public readonly ?array $meta = null
    ) {}
}
