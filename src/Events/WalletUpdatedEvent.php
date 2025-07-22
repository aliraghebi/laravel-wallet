<?php

namespace AliRaghebi\Wallet\Events;

use AliRaghebi\Wallet\Contracts\Events\EventInterface;
use AliRaghebi\Wallet\Models\Wallet;
use DateTimeImmutable;

final readonly class WalletUpdatedEvent implements EventInterface {
    public function __construct(
        public int $walletId,
        public string $walletUuid,
        public string $balance,
        public string $frozenAmount,
        public string $availableBalance,
        public DateTimeImmutable $updatedAt
    ) {}

    public static function fromWallet(Wallet $wallet): self {
        return new self(
            $wallet->id,
            $wallet->uuid,
            $wallet->balance,
            $wallet->frozen_amount,
            $wallet->available_balance,
            DateTimeImmutable::createFromMutable($wallet->updated_at),
        );
    }
}
