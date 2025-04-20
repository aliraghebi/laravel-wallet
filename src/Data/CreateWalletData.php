<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\BaseData;

class CreateWalletData extends BaseData {
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $slug = null,
        public ?int $decimalPlaces = null,
        public ?string $description = null,
        public ?array $meta = null,
    ) {}
}
