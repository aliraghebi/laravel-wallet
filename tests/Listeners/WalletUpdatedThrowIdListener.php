<?php

namespace AliRaghebi\Wallet\Test\Listeners;

use AliRaghebi\Wallet\Events\WalletUpdatedEvent;
use AliRaghebi\Wallet\Test\Exceptions\UnknownEventException;

final class WalletUpdatedThrowIdListener {
    public function handle(WalletUpdatedEvent $event): never {
        throw new UnknownEventException(
            $event->walletUuid,
            (int) $event->balance
        );
    }
}
