<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Services\BookkeeperServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\LockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\StorageServiceInterface;
use AliRaghebi\Wallet\Data\WalletStateData;
use AliRaghebi\Wallet\Exceptions\RecordNotFoundException;

readonly class BookkeeperService implements BookkeeperServiceInterface {
    public function __construct(
        private StorageServiceInterface $storageService,
        private LockServiceInterface $lockService,
        private WalletRepositoryInterface $walletRepository
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
                function () use ($wallets, $recordNotFoundException) {
                    $results = [];

                    $fWallets = $this->walletRepository->multiGet($recordNotFoundException->getMissingKeys(), 'uuid')->mapWithKeys(fn ($item) => [$item->uuid => $item])->all();

                    /** @var Wallet $wallet */
                    foreach ($recordNotFoundException->getMissingKeys() as $uuid) {
                        $wallet = $fWallets[$uuid] ?? $wallets[$uuid];

                        $results[$uuid] = new WalletStateData(
                            $wallet->getRawOriginal('balance', '0'),
                            $wallet->getRawOriginal('frozen_amount', '0'),
                        );
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
