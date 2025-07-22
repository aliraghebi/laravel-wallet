<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;
use DateTimeImmutable;

class WalletData extends BaseData {
    public function __construct(
        public readonly string $uuid,
        public readonly string $holderType,
        public readonly int|string $holderId,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $decimalPlaces,
        public readonly ?string $description,
        public readonly ?array $meta,
        public readonly ?string $checksum,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}
}
