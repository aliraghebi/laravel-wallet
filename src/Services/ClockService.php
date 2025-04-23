<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Services\ClockServiceInterface;
use DateTimeImmutable;

class ClockService implements ClockServiceInterface {
    public function now(): DateTimeImmutable {
        return new DateTimeImmutable;
    }
}
