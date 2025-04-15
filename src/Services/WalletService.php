<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Data\CreateTransactionData;
use ArsamMe\Wallet\Data\CreateWalletData;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Str;

readonly class WalletService implements WalletServiceInterface {
    public function __construct(
        private AtomicServiceInterface $atomicService,
        private MathServiceInterface $mathService,
        private ConsistencyServiceInterface $consistencyService,
        private RegulatorServiceInterface $regulatorService,
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function createWallet(CreateWalletData $data): Wallet {
        $defaultParams = array_filter(config('wallet.creating', []));

        $uuid     = Str::uuid7();
        $time     = now();
        $checksum = $this->consistencyService->createWalletInitialChecksum($uuid, $time);

        $attributes = array_filter([
            'uuid'           => $uuid,
            'holder_type'    => $data->holder->getMorphClass(),
            'holder_id'      => $data->holder->getKey(),
            'name'           => $data->name,
            'slug'           => $data->slug,
            'description'    => $data->description,
            'decimal_places' => $data->decimalPlaces,
            'meta'           => $data->meta,
            'checksum'       => $checksum,
            'created_at'     => $time,
            'updated_at'     => $time,
        ]);

        $attributes = array_merge($defaultParams, $attributes);

        return $this->walletRepository->createWallet($attributes);
    }

    public function findById(int $id): ?Wallet {
        return $this->walletRepository->findById($id);
    }

    public function findByUuid(string $uuid): ?Wallet {
        $this->walletRepository->findByUuid($uuid);
    }

    public function findBySlug(Model $holder, string $slug): ?Wallet {
        return $this->walletRepository->findBySlug($holder->getMorphClass(), $holder->getKey(), $slug);
    }

    public function findOrFailById(int $id): Wallet {
        return $this->walletRepository->findOrFailById($id);
    }

    public function findOrFailByUuid(string $uuid): Wallet {
        return $this->walletRepository->findOrFailByUuid($uuid);
    }

    public function findOrFailBySlug(Model $holder, string $slug): Wallet {
        return $this->walletRepository->findOrFailBySlug($holder->getMorphClass(), $holder->getKey(), $slug);
    }

    public function getBalance(Wallet $wallet): string {
        return $wallet->getBalanceAttribute();
    }

    public function deposit(Wallet $wallet, CreateTransactionData $data): void {
        $this->atomic($wallet, function () use ($wallet, $data) {
            $amount = $this->mathService->intValue($data->amount, $wallet->decimal_places);

            $this->consistencyService->checkPositive($amount);

            $this->makeTransaction($wallet, Transaction::TYPE_DEPOSIT, $amount, $data);

            $this->regulatorService->increase($wallet, $amount);
        });
    }

    public function withdraw(Wallet $wallet, CreateTransactionData $data): void {
        $this->atomic($wallet, function () use ($wallet, $data) {
            $amount = $this->mathService->intValue($data->amount, $wallet->decimal_places);

            $this->consistencyService->checkPositive($amount);
            $this->consistencyService->checkPotential($wallet, $amount);

            $this->makeTransaction($wallet, Transaction::TYPE_WITHDRAW, $amount, $data);

            $this->regulatorService->decrease($wallet, $amount);
        });
    }

    public function freeze(Wallet $wallet, float|int|string|null $amount = null): void {
        $this->atomic($wallet, function () use ($amount, $wallet) {
            if (null != $amount) {
                $amount = $this->mathService->intValue($amount, $wallet->decimal_places);
                $this->consistencyService->checkPositive($amount);
            }

            $this->regulatorService->freeze($wallet, $amount);
        });
    }

    public function unFreeze(Wallet $wallet, float|int|string|null $amount = null): void {
        $this->atomic($wallet, function () use ($amount, $wallet) {
            if (null != $amount) {
                $amount = $this->mathService->intValue($amount, $wallet->decimal_places);
                $this->consistencyService->checkPositive($amount);
            }

            $this->regulatorService->unFreeze($wallet, $amount);
        });
    }

    public function atomic(array|Wallet $wallets, $callback): mixed {
        if ($wallets instanceof Wallet) {
            $wallets = [$wallets];
        }

        return $this->atomicService->blocks($wallets, $callback);
    }

    public function checkWalletConsistency(Wallet $wallet, bool $throw = false): bool {
        return $this->consistencyService->checkWalletConsistency($wallet, $throw);
    }

    private function makeTransaction(Wallet $wallet, string $type, string $amount, CreateTransactionData $data): void {
        $uuid     = Str::uuid7();
        $time     = now();
        $amount   = Transaction::TYPE_WITHDRAW == $type ? $this->mathService->negative($amount) : $amount;
        $checksum = $this->consistencyService->createTransactionChecksum($uuid, $wallet->id, $type, $amount, $time);

        $attributes = [
            'uuid'       => $uuid,
            'wallet_id'  => $wallet->id,
            'type'       => $type,
            'amount'     => $amount,
            'meta'       => $data->meta,
            'checksum'   => $checksum,
            'created_at' => $time,
            'updated_at' => $time,
        ];

        $this->transactionRepository->createTransaction($attributes);
    }
}
