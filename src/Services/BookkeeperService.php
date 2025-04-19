<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use ArsamMe\Wallet\Data\WalletStateData;
use ArsamMe\Wallet\Exceptions\RecordNotFoundException;
use ArsamMe\Wallet\Models\Wallet;

readonly class BookkeeperService implements BookkeeperServiceInterface {
    public function __construct(
        private StorageServiceInterface $storageService,
        private LockServiceInterface $lockService,
        private WalletRepositoryInterface $walletRepository,
    ) {}

    public function forget(Wallet $wallet): bool {
        return $this->storageService->forget($wallet->uuid);
    }

    public function getBalance(Wallet $wallet): string {
        return $this->get($wallet)->balance;
    }

    public function getFrozenAmount(Wallet $wallet): string {
        return $this->get($wallet)->frozenAmount;
    }

    public function getTransactionsCount(Wallet $wallet): int {
        return $this->get($wallet)->transactionsCount;
    }

    public function sync(Wallet $wallet, WalletStateData $data): bool {
        return $this->multiSync([$wallet->uuid => $data]);
    }

    public function get(Wallet $wallet): WalletStateData {
        return current($this->multiGet([
            $wallet->uuid => $wallet,
        ]));
    }

    public function multiGet(array $wallets): array {
        try {
            return $this->storageService->multiGet(array_keys($wallets));
        } catch (RecordNotFoundException $recordNotFoundException) {
            $this->lockService->blocks(
                $recordNotFoundException->getMissingKeys(),
                function () use ($recordNotFoundException) {
                    $results = [];
                    $wallets = $this->walletRepository->multiGet($recordNotFoundException->getMissingKeys(), 'uuid');
                    /** @var Wallet $wallet */
                    foreach ($wallets as $uuid => $wallet) {
                        $results[$uuid] = new WalletStateData($wallet->getRawBalanceAttribute(), $wallet->getRawFrozenAmountAttribute(), $wallet->transactions_count);
                    }

                    $this->multiSync($results);
                }
            );
        }

        return $this->storageService->multiGet(array_keys($wallets));
    }

    public function multiSync(array $items): bool {
        return $this->storageService->multiSync($items);
    }
}
