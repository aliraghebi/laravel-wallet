<?php

namespace ArsamMe\Wallet\Test\Listeners;

use ArsamMe\Wallet\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Test\Exceptions\UnknownEventException;
use DateTimeInterface;

final class WalletCreatedThrowListener {
    public function handle(WalletCreatedEvent $event): never {
        $holderType = $event->holderType;
        $uuid = $event->uuid;

        $message = hash('sha256', $holderType.$uuid);
        $code = $event->id + (int) $event->holderId;
        assert($code > 1);

        throw new UnknownEventException($message, $code);
    }
}
