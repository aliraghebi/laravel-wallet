<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\BaseData;
use Illuminate\Database\Eloquent\Model;

class CreateWalletData extends BaseData {
    public function __construct(
        public Model $holder,
        public string $name,
        public ?string $slug = null,
        public ?int $decimalPlaces = null,
        public ?string $description = null,
        public ?array $meta = null,
    ) {}
}
