<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Events;

use ArsamMe\Wallet\Contracts\Events\EventInterface;
use DateTimeImmutable;

final readonly class TransactionCreatedEvent implements EventInterface {
    public function __construct(
        private int $id,
        private string $uuid,
        private int $walletId,
        private string $type,
        private string $amount,
        private ?array $meta,
        private DateTimeImmutable $createdAt,
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getUuid(): string {
        return $this->uuid;
    }

    public function getWalletId(): int {
        return $this->walletId;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getAmount(): string {
        return $this->amount;
    }

    public function getMeta(): ?array {
        return $this->meta;
    }

    public function getCreatedAt(): DateTimeImmutable {
        return $this->createdAt;
    }
}
