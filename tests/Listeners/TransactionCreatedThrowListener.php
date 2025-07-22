<?php

namespace AliRaghebi\Wallet\Test\Listeners;

use AliRaghebi\Wallet\Events\TransactionCreatedEvent;
use AliRaghebi\Wallet\Test\Exceptions\UnknownEventException;
use DateTimeInterface;

final class TransactionCreatedThrowListener {
    public function handle(TransactionCreatedEvent $event): never {
        $type = $event->type;

        $createdAt = $event->createdAt->format(DateTimeInterface::ATOM);

        $message = hash('sha256', $type.$createdAt);

        throw new UnknownEventException($message, $event->id);
    }
}
