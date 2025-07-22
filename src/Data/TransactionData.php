<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;
use DateTimeImmutable;

class TransactionData extends BaseData {
    public function __construct(
        public readonly string $uuid,
        public readonly int $walletId,
        public readonly string $type,
        public readonly string $amount,
        public readonly ?string $purpose,
        public readonly ?string $description,
        public readonly ?array $meta,
        public readonly ?string $checksum,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}
}
