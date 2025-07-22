<?php

namespace AliRaghebi\Wallet\Events;

use AliRaghebi\Wallet\Contracts\Events\EventInterface;
use AliRaghebi\Wallet\Models\Wallet;
use DateTimeImmutable;

final readonly class WalletCreatedEvent implements EventInterface {
    public function __construct(
        public int $id,
        public string $uuid,
        public string $holderType,
        public int|string $holderId,
        public ?string $description,
        public ?array $meta,
        public int $decimalPlaces,
        public DateTimeImmutable $createdAt
    ) {}

    public static function fromWallet(Wallet $wallet): self {
        return new self(
            $wallet->id,
            $wallet->uuid,
            $wallet->holder_type,
            $wallet->holder_id,
            $wallet->description,
            $wallet->meta,
            $wallet->decimal_places,
            DateTimeImmutable::createFromMutable($wallet->created_at),
        );
    }
}
