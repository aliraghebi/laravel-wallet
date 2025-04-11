<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Exceptions\TransactionFailedException;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\RecordsNotFoundException;

/**
 * @internal
 */
final readonly class AtomicService implements AtomicServiceInterface {
    public function __construct(
        private DatabaseServiceInterface $databaseService,
        private LockServiceInterface $lockService,
    ) {}

    public function blocks(array $wallets, callable $callback): mixed {
        /** @var array<string, Wallet> $blockObjects */
        $blockObjects = [];
        foreach ($wallets as $wallet) {
            if ($wallet instanceof Wallet && !$this->lockService->isBlocked($wallet->uuid)) {
                $blockObjects[$wallet->uuid] = $wallet;
            }
        }

        if ([] === $blockObjects) {
            return $callback();
        }

        $callable = function () use ($callback) {
            return $this->databaseService->transaction($callback);
        };

        return $this->lockService->blocks(array_keys($blockObjects), $callable);
    }

    /**
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     */
    public function block(Wallet $object, callable $callback): mixed {
        return $this->blocks([$object], $callback);
    }
}
