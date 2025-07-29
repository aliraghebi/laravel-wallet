<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Services\AtomicServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DispatcherServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\RegulatorServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransactionServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransferServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\WalletServiceInterface;
use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Data\WalletData;
use AliRaghebi\Wallet\Events\WalletCreatedEvent;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet as WalletModel;
use AliRaghebi\Wallet\WalletConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class WalletService implements WalletServiceInterface {
    public function __construct(
        private AtomicServiceInterface $atomicService,
        private ConsistencyServiceInterface $consistencyService,
        private RegulatorServiceInterface $regulatorService,
        private WalletRepositoryInterface $walletRepository,
        private TransactionServiceInterface $transactionService,
        private DispatcherServiceInterface $dispatcherService,
        private TransferServiceInterface $transferService,
        private ClockServiceInterface $clockService,
        private IdentifierFactoryServiceInterface $identifierFactoryService,
        private WalletConfig $config
    ) {}

    public function createWallet(
        Model $holder,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?array $meta = null,
        ?string $uuid = null
    ): WalletModel {
        if ($name != null && $slug == null) {
            $slug = Str::slug($name);
        }
        $uuid = $uuid ?? $this->identifierFactoryService->generate();
        $time = $this->clockService->now();
        $checksum = $this->consistencyService->createWalletChecksum($uuid, '0', '0', $time);

        $data = new WalletData(
            $uuid,
            $holder->getMorphClass(),
            $holder->getKey(),
            $name ?? config('wallet.wallet.default.name', 'Default Wallet'),
            $slug ?? config('wallet.wallet.default.slug', 'default'),
            $description,
            $meta,
            $checksum,
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

    public function deposit(Wallet $wallet, int|float|string $amount, ?TransactionExtra $extra = null): Transaction {
        return $this->atomic($wallet, function () use ($extra, $amount, $wallet) {
            $amount = number($amount)->scale($this->config->number_decimal_places)->toString();

            return $this->transactionService->deposit($wallet, $amount, $extra);
        });
    }

    public function withdraw(Wallet $wallet, int|float|string $amount, ?TransactionExtra $extra = null): Transaction {
        return $this->atomic($wallet, function () use ($extra, $amount, $wallet) {
            $amount = number($amount)->scale($this->config->number_decimal_places)->toString();

            return $this->transactionService->withdraw($wallet, $amount, $extra);
        });
    }

    public function transfer(Wallet $from, Wallet $to, float|int|string $amount, float|int|string $fee = 0, ?TransferExtra $extra = null): Transfer {
        return $this->atomic([$from, $to], function () use ($from, $to, $fee, $extra, $amount) {
            return $this->transferService->transfer($from, $to, $amount, $fee, $extra);
        });
    }

    public function freeze(Wallet $wallet, float|int|string|null $amount = null, bool $allowOverdraft = false): bool {
        return $this->atomic($wallet, function () use ($amount, $wallet, $allowOverdraft) {
            if ($amount != null) {
                $amount = number($amount)->scale($this->config->number_decimal_places)->toString();
                $this->consistencyService->checkPositive($amount);
                if (!$allowOverdraft) {
                    $this->consistencyService->checkPotential($wallet, $amount);
                }
            }

            $this->regulatorService->freeze($wallet, $amount);

            return true;
        });
    }

    public function unFreeze(Wallet $wallet, float|int|string|null $amount = null): bool {
        return $this->atomic($wallet, function () use ($amount, $wallet) {
            if ($amount != null) {
                $amount = number($amount)->scale($this->config->number_decimal_places)->toString();
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
        return $this->consistencyService->validateWalletChecksum($wallet, $throw);
    }
}
