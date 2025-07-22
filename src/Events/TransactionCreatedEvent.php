<?php

namespace AliRaghebi\Wallet\Events;

use AliRaghebi\Wallet\Contracts\Events\EventInterface;
use AliRaghebi\Wallet\Models\Transaction;
use DateTimeImmutable;

final readonly class TransactionCreatedEvent implements EventInterface {
    public function __construct(
        public int $id,
        public string $uuid,
        public int $walletId,
        public string $type,
        public string $amount,
        public ?array $meta,
        public DateTimeImmutable $createdAt,
    ) {}

    public static function fromTransaction(Transaction $transaction): self {
        return new self(
            $transaction->id,
            $transaction->uuid,
            $transaction->wallet_id,
            $transaction->type,
            $transaction->amount,
            $transaction->meta,
            DateTimeImmutable::createFromMutable($transaction->created_at),
        );
    }
}
