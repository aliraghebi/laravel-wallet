<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use ArsamMe\Wallet\Data\WalletStateData;
use ArsamMe\Wallet\Events\WalletUpdatedEvent;
use ArsamMe\Wallet\Exceptions\RecordNotFoundException;
use Illuminate\Support\Arr;

class RegulatorService implements RegulatorServiceInterface {
    private array $wallets = [];

    private array $walletChanges = [];

    public function __construct(
        private readonly BookkeeperServiceInterface $bookkeeperService,
        private readonly StorageServiceInterface $storageService,
        private readonly MathServiceInterface $mathService,
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

    public function getTransactionsCountDiff(Wallet $wallet): int {
        try {
            return $this->get($wallet)->transactionsCount;
        } catch (RecordNotFoundException) {
            return 0;
        }
    }

    public function getTransactionsCount(Wallet $wallet): int {
        return $this->bookkeeperService->getTransactionsCount($wallet) + $this->getTransactionsCountDiff($wallet);
    }

    public function get(Wallet $wallet): WalletStateData {
        return $this->storageService->get($wallet->uuid);
    }

    public function getBalance(Wallet $wallet): string {
        return $this->mathService->add($this->bookkeeperService->getBalance($wallet), $this->getBalanceDiff($wallet), 0);
    }

    public function getFrozenAmount(Wallet $wallet): string {
        return $this->mathService->add($this->bookkeeperService->getFrozenAmount($wallet), $this->getFrozenAmountDiff($wallet), 0);
    }

    public function getAvailableBalance(Wallet $wallet): string {
        $availableBalance = $this->mathService->sub($this->getBalance($wallet), $this->getFrozenAmount($wallet), 0);
        if ($this->mathService->compare($availableBalance, 0) == -1) {
            $availableBalance = '0';
        }

        return $availableBalance;
    }

    public function increase(Wallet $wallet, string $value, int $transactionCount = 1): string {
        assert($transactionCount > 0);
        $this->persist($wallet);

        try {
            $data = $this->get($wallet);
            $data->balance = $this->mathService->add($data->balance, $value, 0);
            $data->transactionsCount += $transactionCount;
            $this->storageService->sync($wallet->uuid, $data);
        } catch (RecordNotFoundException) {
            $data = new WalletStateData($value, '0', $transactionCount);
            $this->storageService->sync($wallet->uuid, $data);
        }

        return $this->getBalance($wallet);
    }

    public function decrease(Wallet $wallet, string $value, int $transactionCount = 1): string {
        return $this->increase($wallet, $this->mathService->negative($value), $transactionCount);
    }

    public function freeze(Wallet $wallet, ?string $value = null): string {
        $this->persist($wallet);
        $value ??= $this->getBalance($wallet);

        try {
            $data = $this->get($wallet);
            $data->frozenAmount = $this->mathService->add($data->frozenAmount, $value, 0);
            $this->storageService->sync($wallet->uuid, $data);
        } catch (RecordNotFoundException) {
            $data = new WalletStateData('0', $value, 0);
            $this->storageService->sync($wallet->uuid, $data);
        }

        return $this->getBalance($wallet);
    }

    public function unFreeze(Wallet $wallet, ?string $value = null): string {
        $frozenAmount = $this->getFrozenAmount($wallet);
        if ($value == null) {
            $value = $frozenAmount;
        } else {
            if ($this->mathService->compare($value, $frozenAmount) == 1) {
                $value = $frozenAmount;
            }
        }

        return $this->freeze($wallet, $this->mathService->negative($value));
    }

    public function committing(): void {
        $walletChanges = [];
        $bookkeeperChanges = [];
        foreach ($this->wallets as $wallet) {
            $balanceChanged = $this->mathService->compare($this->getBalanceDiff($wallet), 0) != 0;
            $frozenAmountChanged = $this->mathService->compare($this->getFrozenAmountDiff($wallet), 0) != 0;
            $transactionsCountChanged = $this->mathService->compare($this->getTransactionsCountDiff($wallet), 0) != 0;

            // Check if no changes occurred to the wallet, then skip it
            if (!$balanceChanged && !$frozenAmountChanged && !$transactionsCountChanged) {
                continue;
            }

            $id = $wallet->getKey();
            $uuid = $wallet->uuid;
            $balance = $this->getBalance($wallet);
            $frozenAmount = $this->getFrozenAmount($wallet);
            $transactionsCount = $this->getTransactionsCount($wallet);

            // Fill wallet changes with new data.
            $walletChanges[$uuid] = array_filter([
                'id' => $id,
                'balance' => $balanceChanged ? $balance : null,
                'frozen_amount' => $frozenAmountChanged ? $frozenAmount : null,
                'checksum' => $this->consistencyService->createWalletChecksum($uuid, $balance, $frozenAmount, $transactionsCount, $balance),
            ]);

            // Fill bookkeeper changes with new data. We need to update the bookkeeper with the new balance and frozen amount.
            $bookkeeperChanges[$wallet->uuid] = new WalletStateData($balance, $frozenAmount, $transactionsCount);
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
            $this->consistencyService->checkMultiWalletConsistency($checksums);
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
