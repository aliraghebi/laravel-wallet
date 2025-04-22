<?php

namespace ArsamMe\Wallet\Test\Infra\Listeners;

use ArsamMe\Wallet\Internal\Events\BalanceUpdatedEventInterface;
use ArsamMe\Wallet\Test\Infra\Exceptions\UnknownEventException;

final class BalanceUpdatedThrowUuidListener {
    public function handle(BalanceUpdatedEventInterface $balanceChangedEvent): never {
        throw new UnknownEventException(
            $balanceChangedEvent->getWalletUuid(),
            ((int) $balanceChangedEvent->getBalance()) + $balanceChangedEvent->getWalletId(),
        );
    }
}
