<?php

namespace AliRaghebi\Wallet\Events;

use AliRaghebi\Wallet\Contracts\Events\EventInterface;
use AliRaghebi\Wallet\Models\Transfer;
use DateTimeImmutable;

final readonly class TransferCreatedEvent implements EventInterface {
    public function __construct(
        public int $id,
        public string $uuid,
        public int $fromWalletId,
        public int $toWalletId,
        public string $amount,
        public string $fee,
        public ?array $meta,
        public DateTimeImmutable $createdAt,
    ) {}

    public static function fromTransfer(Transfer $transfer): self {
        return new self(
            $transfer->id,
            $transfer->uuid,
            $transfer->from_id,
            $transfer->to_id,
            $transfer->amount,
            $transfer->fee,
            $transfer->meta,
            DateTimeImmutable::createFromMutable($transfer->created_at),
        );
    }
}
