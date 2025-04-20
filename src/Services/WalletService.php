<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Data\CreateWalletData;
use ArsamMe\Wallet\Events\TransactionCreatedEvent;
use ArsamMe\Wallet\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class WalletService implements WalletServiceInterface {
    public function __construct(
        private AtomicServiceInterface $atomicService,
        private MathServiceInterface $mathService,
        private ConsistencyServiceInterface $consistencyService,
        private RegulatorServiceInterface $regulatorService,
        private WalletRepositoryInterface $walletRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private DispatcherServiceInterface $dispatcherService
    ) {}

    public function createWallet(Model $holder, ?CreateWalletData $data = null): Wallet {
        $name = $data?->name;
        $slug = $data?->slug;

        if ($name != null && $slug == null) {
            $slug = Str::slug($name);
        }

        $attributes = array_merge(
            config('wallet.wallet.default', []),
            array_filter([
                'uuid' => $data->uuid ?? Str::uuid7()->toString(),
                'holder_type' => $holder->getMorphClass(),
                'holder_id' => $holder->getKey(),
                'name' => $name,
                'slug' => $slug,
                'description' => $data?->description,
                'decimal_places' => $data?->decimalPlaces,
                'meta' => $data?->meta,
            ])
        );

        $wallet = $this->walletRepository->createWallet($attributes);

        $this->dispatcherService->dispatch(new WalletCreatedEvent(
            $wallet->id,
            $wallet->uuid,
            $wallet->holder_type,
            $wallet->holder_id,
            $wallet->description,
            $wallet->meta,
            $wallet->decimal_places,
            $wallet->created_at->toImmutable(),
        ));

        $this->dispatcherService->lazyFlush();

        return $wallet;
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

    public function deposit(Wallet $wallet, int|float|string $amount, ?array $meta = null): Transaction {
        return $this->atomic($wallet, function () use ($meta, $amount, $wallet) {
            $amount = $this->mathService->intValue($amount, $wallet->decimal_places);

            $this->consistencyService->checkPositive($amount);

            $transaction = $this->makeTransaction($wallet, Transaction::TYPE_DEPOSIT, $amount, $meta);

            $this->regulatorService->increase($wallet, $amount);

            return $transaction;
        });
    }

    public function withdraw(Wallet $wallet, int|float|string $amount, ?array $meta = null): Transaction {
        return $this->atomic($wallet, function () use ($meta, $amount, $wallet) {
            $amount = $this->mathService->intValue($amount, $wallet->decimal_places);

            $this->consistencyService->checkPositive($amount);
            $this->consistencyService->checkPotential($wallet, $amount);

            $transaction = $this->makeTransaction($wallet, Transaction::TYPE_WITHDRAW, $amount, $meta);

            $this->regulatorService->decrease($wallet, $amount);

            return $transaction;
        });
    }

    public function freeze(Wallet $wallet, float|int|string|null $amount = null): bool {
        return $this->atomic($wallet, function () use ($amount, $wallet) {
            if ($amount != null) {
                $amount = $this->mathService->intValue($amount, $wallet->decimal_places);
                $this->consistencyService->checkPositive($amount);
            }

            $this->regulatorService->freeze($wallet, $amount);

            return true;
        });
    }

    public function unFreeze(Wallet $wallet, float|int|string|null $amount = null): bool {
        return $this->atomic($wallet, function () use ($amount, $wallet) {
            if ($amount != null) {
                $amount = $this->mathService->intValue($amount, $wallet->decimal_places);
                $this->consistencyService->checkPositive($amount);
            }

            $this->regulatorService->unFreeze($wallet, $amount);

            return true;
        });
    }

    public function atomic(Collection|Wallet|array $wallets, $callback): mixed {
        if ($wallets instanceof Wallet) {
            $wallets = [$wallets];
        }

        return $this->atomicService->blocks($wallets, $callback);
    }

    public function checkWalletConsistency(Wallet $wallet, bool $throw = false): bool {
        return $this->consistencyService->checkWalletConsistency($wallet, $throw);
    }

    private function makeTransaction(Wallet $wallet, string $type, string $amount, ?array $meta = null): Transaction {
        $uuid = Str::uuid7()->toString();
        $time = now();
        $amount = $type == Transaction::TYPE_WITHDRAW ? $this->mathService->negative($amount) : $amount;
        $checksum = $this->consistencyService->createTransactionChecksum($uuid, $wallet->id, $type, $amount, $time);

        $attributes = [
            'uuid' => $uuid,
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => $amount,
            'meta' => $meta,
            'checksum' => $checksum,
            'created_at' => $time,
            'updated_at' => $time,
        ];

        $transaction = $this->transactionRepository->createTransaction($attributes);

        $this->dispatcherService->dispatch(new TransactionCreatedEvent(
            $transaction->id,
            $transaction->uuid,
            $transaction->wallet_id,
            $transaction->type,
            $transaction->amount,
            $transaction->meta,
            $transaction->created_at->toImmutable()
        ));

        return $transaction;
    }
}
