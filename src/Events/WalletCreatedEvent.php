<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Events;

use ArsamMe\Wallet\Contracts\Events\EventInterface;
use DateTimeImmutable;

final readonly class WalletCreatedEvent implements EventInterface {
    public function __construct(
        private int $id,
        private string $uuid,
        private string $holderType,
        private int|string $holderId,
        private ?string $description,
        private ?array $meta,
        private int $decimalPlaces,
        private DateTimeImmutable $createdAt
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getUuid(): string {
        return $this->uuid;
    }

    public function getHolderType(): string {
        return $this->holderType;
    }

    public function getHolderId(): int|string {
        return $this->holderId;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function getMeta(): ?array {
        return $this->meta;
    }

    public function getDecimalPlaces(): int {
        return $this->decimalPlaces;
    }

    public function getCreatedAt(): DateTimeImmutable {
        return $this->createdAt;
    }
}
