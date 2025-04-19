<?php

namespace ArsamMe\Wallet\Services;

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
use ArsamMe\Wallet\Models\Wallet;

class RegulatorService implements RegulatorServiceInterface {
    private array $wallets = [];

    private array $changes = [];

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
        $this->bookkeeperService->forget($wallet);

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
        return $this->storageService->get($wallet->uuid, WalletStateData::class);
    }

    public function getBalance(Wallet $wallet): string {
        return $this->mathService->add($this->bookkeeperService->getBalance($wallet), $this->getBalanceDiff($wallet));
    }

    public function getFrozenAmount(Wallet $wallet): string {
        return $this->mathService->add($this->bookkeeperService->getFrozenAmount($wallet), $this->getFrozenAmountDiff($wallet));
    }

    public function getAvailableBalance(Wallet $wallet): string {
        $availableBalance = $this->mathService->sub($this->getBalance($wallet), $this->getFrozenAmount($wallet));
        if (-1 == $this->mathService->compare($availableBalance, 0)) {
            $availableBalance = '0';
        }

        return $availableBalance;
    }

    public function increase(Wallet $wallet, string $value): string {
        $this->persist($wallet);

        try {
            $data = $this->get($wallet);
            $data->balance = $this->mathService->add($data->balance, $value);
            $data->transactionsCount++;
            $this->storageService->sync($wallet->uuid, $data);
        } catch (RecordNotFoundException) {
            $data = WalletStateData::make([
                'uuid'              => $wallet->uuid,
                'balance'           => $value,
                'transactionsCount' => 1,
            ]);
            $this->storageService->sync($wallet->uuid, $data);
        }

        return $this->getBalance($wallet);
    }

    public function decrease(Wallet $wallet, string $value): string {
        return $this->increase($wallet, $this->mathService->negative($value));
    }

    public function freeze(Wallet $wallet, ?string $value = null): string {
        $this->persist($wallet);
        $value ??= $this->getBalance($wallet);

        try {
            $data = $this->get($wallet);
            $data->frozenAmount = $this->mathService->add($data->frozenAmount, $value);
            $this->storageService->sync($wallet->uuid, $data);
        } catch (RecordNotFoundException) {
            $data = WalletStateData::make([
                'uuid'         => $wallet->uuid,
                'frozenAmount' => $value,
            ]);
            $this->storageService->sync($wallet->uuid, $data);
        }

        return $this->getBalance($wallet);
    }

    public function unFreeze(Wallet $wallet, ?string $value = null): string {
        $frozenAmount = $this->getFrozenAmount($wallet);
        if (null == $value) {
            $value = $frozenAmount;
        } else {
            if (1 == $this->mathService->compare($value, $frozenAmount)) {
                $value = $frozenAmount;
            }
        }

        return $this->freeze($wallet, $this->mathService->negative($value));
    }

    public function committing(): void {
        $changes = [];
        foreach ($this->wallets as $wallet) {
            $frozenAmountDiff = $this->getFrozenAmountDiff($wallet);
            $transactionsCountDiff = $this->getTransactionsCountDiff($wallet);
            if (0 === $this->mathService->compare($transactionsCountDiff, 0) && 0 === $this->mathService->compare($frozenAmountDiff, 0)) {
                continue;
            }

            $uuid = $wallet->uuid;
            $balance = $this->getBalance($wallet);
            $frozenAmount = $this->getFrozenAmount($wallet);
            $transactionsCount = $this->getTransactionsCount($wallet);
            $checksum = $this->consistencyService->createWalletChecksum($uuid, $balance, $frozenAmount, $transactionsCount, $balance);

            $changes[$wallet->uuid] = new WalletStateData(
                $uuid,
                $balance,
                $frozenAmount,
                $transactionsCount,
                $balance,
                $checksum
            );
        }

        if ([] !== $changes) {
            $updateData = collect($changes)->mapWithKeys(function ($item, $key) {
                return [
                    $this->wallets[$key]->getKey() => [
                        'balance'       => $item->balance,
                        'frozen_amount' => $item->frozenAmount,
                        'checksum'      => $item->checksum,
                    ],
                ];
            })->toArray();

            $this->walletRepository->multiUpdate($updateData);

            $checksums = collect($changes)->mapWithKeys(function ($item, $key) {
                return [
                    $key => $item->checksum,
                ];
            })->toArray();
            $this->consistencyService->checkMultiWalletConsistency($checksums);

            $this->changes = $changes;
            $this->bookkeeperService->multiSync($changes);
        }
    }

    public function committed(): void {
        try {
            /** @var WalletStateData $state */
            foreach ($this->changes as $uuid => $state) {
                $wallet = $this->wallets[$uuid];

                $wallet->fill([
                    'balance'       => $state->balance,
                    'frozen_amount' => $state->frozenAmount,
                    'checksum'      => $state->checksum,
                ])->syncOriginalAttributes(['balance', 'frozen_amount', 'checksum']);

                $this->dispatcherService->dispatch(new WalletUpdatedEvent(
                    $wallet->id,
                    $wallet->uuid,
                    $wallet->balance,
                    $wallet->frozen_amount,
                    $wallet->available_balance,
                    $wallet->updated_at->toImmutable()
                ));
            }
        } finally {
            $this->dispatcherService->flush();
            $this->purge();
        }
    }

    public function purge(): void {
        try {
            $this->lockService->releases(array_keys($this->wallets));
            $this->changes = [];
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
