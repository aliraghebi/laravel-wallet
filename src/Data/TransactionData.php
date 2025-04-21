<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\BaseData;
use DateTimeImmutable;

class TransactionData extends BaseData {
    public function __construct(
        public readonly string $uuid,
        public readonly int $walletId,
        public readonly string $type,
        public readonly string $amount,
        public readonly ?array $meta,
        public readonly ?string $checksum,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}
}
