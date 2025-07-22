<?php

namespace AliRaghebi\Wallet\Test\Listeners;

use AliRaghebi\Wallet\Events\WalletCreatedEvent;
use AliRaghebi\Wallet\Test\Exceptions\UnknownEventException;

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
