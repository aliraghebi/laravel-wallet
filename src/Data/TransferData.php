<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\Data\BaseData;
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
        public readonly int $decimalPlaces,
        public readonly ?array $meta,
        public readonly ?string $checksum,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {}
}
