<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StateServiceInterface;
use ArsamMe\Wallet\Exceptions\TransactionFailedException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Collection;

/**
 * @internal
 */
final readonly class AtomicService implements AtomicServiceInterface {
    public function __construct(
        private DatabaseServiceInterface $databaseService,
        private LockServiceInterface $lockService,
        private BookkeeperServiceInterface $bookkeeperService,
        private StateServiceInterface $stateService
    ) {}

    public function blocks(Collection|Wallet|array $wallets, callable $callback): mixed {
        /** @var array<string, Wallet> $blockObjects */
        $blockObjects = [];
        foreach ($wallets as $wallet) {
            if ($wallet instanceof Wallet && !$this->lockService->isBlocked($wallet->uuid)) {
                $blockObjects[$wallet->uuid] = $wallet;
            }
        }

        if ($blockObjects === []) {
            return $callback();
        }

        $callable = function () use ($blockObjects, $callback) {
            $this->stateService->multiFork(
                array_keys($blockObjects),
                fn () => $this->bookkeeperService->multiGet($blockObjects)
            );

            return $this->databaseService->transaction($callback);
        };

        try {
            return $this->lockService->blocks(array_keys($blockObjects), $callable);
        } finally {
            foreach (array_keys($blockObjects) as $uuid) {
                $this->stateService->drop($uuid);
            }
        }
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
