<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;
use DateTimeImmutable;

class TransactionExtra extends BaseData {
    public function __construct(
        public readonly ?string $uuid = null,
        public readonly ?string $purpose = null,
        public readonly ?string $description = null,
        public readonly ?array $meta = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {}
}
