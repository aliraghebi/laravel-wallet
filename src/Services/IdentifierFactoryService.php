<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Services\ClockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Str;

final readonly class IdentifierFactoryService implements IdentifierFactoryServiceInterface {
    /**
     * @param  ClockServiceInterface  $clockService  Service for getting the current time.
     */
    public function __construct(
        private ClockServiceInterface $clockService,
    ) {}

    /**
     * Generate a ID string using the uuid7 algorithm.
     *
     * uuid7 is a time-based UUID algorithm that uses the current time in milliseconds,
     * combined with a random number to generate a unique ID.
     *
     * @return non-empty-string The generated ID string.
     *
     * @throws InvalidArgumentException If a field is invalid in the UUID.
     * @throws InvalidUuidStringException If the string we are parsing is not a valid UUID.
     * @throws UnsupportedOperationException If the UUID implementation can't support a feature.
     */
    public function generate(): string {
        return Str::uuid7($this->clockService->now())->toString();
    }
}
