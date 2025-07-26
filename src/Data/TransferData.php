<?php

namespace AliRaghebi\Wallet\Data;

use AliRaghebi\Wallet\Contracts\Data\BaseData;
use DateTimeImmutable;

/** @immutable */
final class TransferData extends BaseData {
    public function __construct(
        public readonly string $uuid,
        public readonly int $depositId,
        public readonly int $withdrawalId,
        public readonly int $fromId,
        public readonly int $toId,
        public readonly string $amount,
        public readonly string $fee,
        public readonly ?string $purpose,
        public readonly ?string $description,
        public readonly ?array $meta,
        public readonly ?string $checksum,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}
}
