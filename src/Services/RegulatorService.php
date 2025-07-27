<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Services\BookkeeperServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DispatcherServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\LockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\RegulatorServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\StorageServiceInterface;
use AliRaghebi\Wallet\Data\WalletStateData;
use AliRaghebi\Wallet\Events\WalletUpdatedEvent;
use AliRaghebi\Wallet\Exceptions\RecordNotFoundException;
use Illuminate\Support\Arr;

class RegulatorService implements RegulatorServiceInterface {
    private array $wallets = [];

    private array $walletChanges = [];

    public function __construct(
        private readonly BookkeeperServiceInterface $bookkeeperService,
        private readonly StorageServiceInterface $storageService,
        private readonly LockServiceInterface $lockService,
        private readonly ConsistencyServiceInterface $consistencyService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly DispatcherServiceInterface $dispatcherService,
    ) {}

    public function forget(Wallet $wallet): bool {
        unset($this->wallets[$wallet->uuid]);

        return $this->storageService->forget($wallet->uuid);
    }

    public function getBalanceDiff(Wallet $wallet): string {
        try {
            return $this->get($wallet)->balance;
        } catch (RecordNotFoundException) {
            return '0';
        }
    }

    public function getFrozenAmountDiff(Wallet $wallet): string {
        try {
            return $this->get($wallet)->frozenAmount;
        } catch (RecordNotFoundException) {
            return '0';
        }
    }

    public function get(Wallet $wallet): WalletStateData {
        return $this->storageService->get($wallet->uuid);
    }

    public function getBalance(Wallet $wallet): string {
        $balance = $this->bookkeeperService->getBalance($wallet);
        $diff = $this->getBalanceDiff($wallet);

        return number($balance)->plus($diff)->toString();
    }

    public function getFrozenAmount(Wallet $wallet): string {
        $frozen = $this->bookkeeperService->getFrozenAmount($wallet);
        $diff = $this->getFrozenAmountDiff($wallet);

        return number($frozen)->plus($diff)->toString();
    }

    public function getAvailableBalance(Wallet $wallet): string {
        $balance = $this->getBalance($wallet);
        $frozen = $this->getFrozenAmount($wallet);

        $available = number($balance)->minus($frozen);
        if ($available->isLessThan(0)) {
            $available = '0';
        }

        return $available;
    }

    public function increase(Wallet $wallet, string $value): string {
        $this->persist($wallet);

        try {
            $data = $this->get($wallet);
            $data->balance = number($data->balance)->plus($value);
            $this->storageService->sync($wallet->uuid, $data);
        } catch (RecordNotFoundException) {
            $data = new WalletStateData($value, '0');
            $this->storageService->sync($wallet->uuid, $data);
        }

        return $this->getBalance($wallet);
    }

    public function decrease(Wallet $wallet, string $value): string {
        return $this->increase($wallet, number($value)->negated());
    }

    public function freeze(Wallet $wallet, ?string $value = null): string {
        $this->persist($wallet);
        $value ??= $this->getBalance($wallet);

        try {
            $data = $this->get($wallet);
            $data->frozenAmount = number($data->frozenAmount)->plus($value);
            $this->storageService->sync($wallet->uuid, $data);
        } catch (RecordNotFoundException) {
            $data = new WalletStateData('0', $value);
            $this->storageService->sync($wallet->uuid, $data);
        }

        return $this->getBalance($wallet);
    }

    public function unFreeze(Wallet $wallet, ?string $value = null): string {
        $frozenAmount = $this->getFrozenAmount($wallet);
        if ($value == null) {
            $value = $frozenAmount;
        } elseif (number($value)->isGreaterThan($frozenAmount)) {
            $value = $frozenAmount;
        }

        return $this->freeze($wallet, number($value)->negated());
    }

    public function committing(): void {
        $walletChanges = [];
        $bookkeeperChanges = [];
        foreach ($this->wallets as $wallet) {
            $balanceChanged = !number($this->getBalanceDiff($wallet))->isEqual(0);
            $frozenAmountChanged = !number($this->getFrozenAmountDiff($wallet))->isEqual(0);

            // Check if no changes occurred to the wallet, then skip it
            if (!$balanceChanged && !$frozenAmountChanged) {
                continue;
            }

            $id = $wallet->getKey();
            $uuid = $wallet->uuid;
            $balance = $this->getBalance($wallet);
            $frozenAmount = $this->getFrozenAmount($wallet);

            // Fill wallet changes with new data.
            $walletChanges[$uuid] = array_filter([
                'id' => $id,
                'balance' => $balanceChanged ? $balance : null,
                'frozen_amount' => $frozenAmountChanged ? $frozenAmount : null,
                'checksum' => $this->consistencyService->createWalletChecksum($uuid, $balance, $frozenAmount, 0, $balance),
            ], fn ($value) => !is_null($value) && $value !== '');

            // Fill bookkeeper changes with new data. We need to update the bookkeeper with the new balance and frozen amount.
            $bookkeeperChanges[$wallet->uuid] = new WalletStateData($balance, $frozenAmount);
        }

        if ($walletChanges !== []) {
            // map changes into key => value array where key is the `id` of wallet and value is array of changes like `balance`, `frozen_amount`
            $changes = array_combine(
                array_column($walletChanges, 'id'),
                array_map(fn ($item) => array_diff_key($item, ['id' => null]), $walletChanges)
            );
            $this->walletRepository->multiUpdate($changes);

            // create a key => value array where key is the `id` of wallet and value is created `checksum` for wallet
            $checksums = array_column($walletChanges, 'checksum', 'id');
        }

        // Set wallet changes variable so we can use later in committed method.
        $this->walletChanges = $walletChanges;

        // Sync bookkeeper with new data.
        if ($bookkeeperChanges !== []) {
            $this->bookkeeperService->multiSync($bookkeeperChanges);
        }
    }

    public function committed(): void {
        try {
            foreach ($this->walletChanges as $uuid => $changes) {
                $wallet = $this->wallets[$uuid];

                $changes = Arr::only($changes, ['balance', 'frozen_amount', 'checksum']);
                $wallet->fill($changes)->syncOriginalAttributes(array_keys($changes));

                $this->dispatcherService->dispatch(WalletUpdatedEvent::fromWallet($wallet));
            }
        } finally {
            $this->dispatcherService->flush();
            $this->purge();
        }
    }

    public function purge(): void {
        try {
            $this->lockService->releases(array_keys($this->wallets));
            $this->walletChanges = [];
            foreach ($this->wallets as $wallet) {
                $this->forget($wallet);
            }
        } finally {
            $this->dispatcherService->forgot();
        }
    }

    public function persist(Wallet $wallet): void {
        $this->wallets[$wallet->uuid] = $wallet;
    }
}
