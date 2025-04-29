<?php

namespace ArsamMe\Wallet\Test\Listeners;

use ArsamMe\Wallet\Events\TransactionCreatedEvent;
use ArsamMe\Wallet\Test\Exceptions\UnknownEventException;
use DateTimeInterface;

final class TransactionCreatedThrowListener {
    public function handle(TransactionCreatedEvent $event): never {
        $type = $event->type;

        $createdAt = $event->createdAt->format(DateTimeInterface::ATOM);

        $message = hash('sha256', $type.$createdAt);

        throw new UnknownEventException($message, $event->id);
    }
}
