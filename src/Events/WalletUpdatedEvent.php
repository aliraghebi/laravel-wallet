<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Events;

use ArsamMe\Wallet\Contracts\Events\EventInterface;
use DateTimeImmutable;

final readonly class WalletUpdatedEvent implements EventInterface {
    public function __construct(
        private int $walletId,
        private string $walletUuid,
        private string $balance,
        private string $frozenAmount,
        private string $availableBalance,
        private DateTimeImmutable $updatedAt
    ) {}

    public function getWalletId(): int {
        return $this->walletId;
    }

    public function getWalletUuid(): string {
        return $this->walletUuid;
    }

    public function getBalance(): string {
        return $this->balance;
    }

    public function getFrozenAmount(): string {
        return $this->frozenAmount;
    }

    public function getAvailableBalance(): string {
        return $this->availableBalance;
    }

    public function getUpdatedAt(): DateTimeImmutable {
        return $this->updatedAt;
    }
}
