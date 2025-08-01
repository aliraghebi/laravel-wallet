<?php

namespace AliRaghebi\Wallet\Test\Services;

use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use DateTimeImmutable;

final class ClockFakeService implements ClockServiceInterface {
    public const FAKE_DATETIME = '2010-01-28T15:00:00';

    public function now(): DateTimeImmutable {
        return new DateTimeImmutable(self::FAKE_DATETIME);
    }
}
