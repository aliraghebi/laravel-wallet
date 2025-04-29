<?php

namespace ArsamMe\Wallet\Test\Listeners;

use ArsamMe\Wallet\Events\WalletUpdatedEvent;
use ArsamMe\Wallet\Test\Exceptions\UnknownEventException;

final class WalletUpdatedThrowIdListener {
    public function handle(WalletUpdatedEvent $event): never {
        throw new UnknownEventException(
            $event->walletUuid,
            (int) $event->balance
        );
    }
}
