<?php

namespace ArsamMe\Wallet\Test\Infra\Listeners;

use ArsamMe\Wallet\Internal\Events\BalanceUpdatedEventInterface;
use ArsamMe\Wallet\Test\Infra\Exceptions\UnknownEventException;
use DateTimeInterface;

final class BalanceUpdatedThrowDateListener {
    public function handle(BalanceUpdatedEventInterface $balanceChangedEvent): never {
        throw new UnknownEventException(
            $balanceChangedEvent->getUpdatedAt()
                ->format(DateTimeInterface::ATOM),
            (int) $balanceChangedEvent->getBalance()
        );
    }
}
