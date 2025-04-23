<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ClockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use ArsamMe\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\TransactionServiceInterface;
use ArsamMe\Wallet\Contracts\Services\TransferServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Data\WalletData;
use ArsamMe\Wallet\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
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
        private TransactionServiceInterface $transactionService,
        private DispatcherServiceInterface $dispatcherService,
        private TransferServiceInterface $transferService,
        private ClockServiceInterface $clockService,
        private IdentifierFactoryServiceInterface $identifierFactoryService
    ) {}

    public function createWallet(
        Model $holder,
        ?string $name = null,
        ?string $slug = null,
        ?int $decimalPlaces = null,
        ?string $description = null,
        ?array $meta = null,
        ?string $uuid = null
    ): WalletModel {
        if ($name != null && $slug == null) {
            $slug = Str::slug($name);
        }

        $time = $this->clockService->now();
        $data = new WalletData(
            $uuid ?? $this->identifierFactoryService->generate(),
            $holder->getMorphClass(),
            $holder->getKey(),
            $name ?? config('wallet.wallet.default.name', 'Default Wallet'),
            $slug ?? config('wallet.wallet.default.slug', 'default'),
            $decimalPlaces ?? config('wallet.wallet.default.decimal_places', 2),
            $description,
            $meta,
            null,
            $time,
            $time
        );

        $wallet = $this->walletRepository->createWallet($data);

        $this->dispatcherService->dispatch(WalletCreatedEvent::fromWallet($wallet));

        $this->dispatcherService->lazyFlush();

        return $wallet;
    }

    public function findById(int $id): ?WalletModel {
        return $this->walletRepository->findBy(['id' => $id]);
    }

    public function findByUuid(string $uuid): ?WalletModel {
        return $this->walletRepository->findBy(['uuid' => $uuid]);
    }

    public function findBySlug(Model $holder, string $slug): ?WalletModel {
        return $this->walletRepository->findBy([
            'holder_type' => $holder->getMorphClass(),
            'holder_id' => $holder->getKey(),
            'slug' => $slug,
        ]);
    }

    public function findOrFailById(int $id): WalletModel {
        return $this->walletRepository->findOrFailBy(['id' => $id]);
    }

    public function findOrFailByUuid(string $uuid): WalletModel {
        return $this->walletRepository->findOrFailBy(['uuid' => $uuid]);
    }

    public function findOrFailBySlug(Model $holder, string $slug): WalletModel {
        return $this->walletRepository->findOrFailBy([
            'holder_type' => $holder->getMorphClass(),
            'holder_id' => $holder->getKey(),
            'slug' => $slug,
        ]);
    }

    public function getBalance(Wallet $wallet): string {
        return $wallet->getBalanceAttribute();
    }

    public function deposit(Wallet $wallet, int|float|string $amount, ?array $meta = null): Transaction {
        return $this->atomic($wallet, function () use ($meta, $amount, $wallet) {
            $amount = $this->mathService->intValue($amount, $wallet->decimal_places);

            return $this->transactionService->deposit($wallet, $amount, $meta);
        });
    }

    public function withdraw(Wallet $wallet, int|float|string $amount, ?array $meta = null): Transaction {
        return $this->atomic($wallet, function () use ($meta, $amount, $wallet) {
            $amount = $this->mathService->intValue($amount, $wallet->decimal_places);

            return $this->transactionService->withdraw($wallet, $amount, $meta);
        });
    }

    public function transfer(Wallet $from, Wallet $to, float|int|string $amount, float|int|string $fee = 0, ?array $meta = null): Transfer {
        return $this->atomic([$from, $to], function () use ($from, $to, $fee, $meta, $amount) {
            return $this->transferService->transfer($from, $to, $amount, $fee, $meta);
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
}
