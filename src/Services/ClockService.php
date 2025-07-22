<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use DateTimeImmutable;

class ClockService implements ClockServiceInterface {
    public function now(): DateTimeImmutable {
        return new DateTimeImmutable;
    }
}
