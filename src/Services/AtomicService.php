<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\AtomicServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\BookkeeperServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DatabaseServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\LockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\StateServiceInterface;
use AliRaghebi\Wallet\Exceptions\TransactionFailedException;
use Illuminate\Database\RecordsNotFoundException;

/**
 * @internal
 */
final readonly class AtomicService implements AtomicServiceInterface {
    public function __construct(
        private CastServiceInterface $castService,
        private DatabaseServiceInterface $databaseService,
        private LockServiceInterface $lockService,
        private BookkeeperServiceInterface $bookkeeperService,
        private StateServiceInterface $stateService
    ) {}

    public function blocks(array $objects, callable $callback): mixed {
        /** @var array<string, Wallet> $blockObjects */
        $blockObjects = [];
        foreach ($objects as $object) {
            $wallet = $this->castService->getWallet($object);
            if (!$this->lockService->isBlocked($wallet->uuid)) {
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
