<?php

namespace ArsamMe\Wallet;

use ArsamMe\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use ArsamMe\Wallet\Contracts\Repositories\TransferRepositoryInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StateServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use ArsamMe\Wallet\Contracts\Services\TransactionServiceInterface;
use ArsamMe\Wallet\Contracts\Services\TransferServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Decorators\StorageServiceLockDecorator;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Repositories\TransactionRepository;
use ArsamMe\Wallet\Repositories\TransferRepository;
use ArsamMe\Wallet\Repositories\WalletRepository;
use ArsamMe\Wallet\Services\AtomicService;
use ArsamMe\Wallet\Services\BookkeeperService;
use ArsamMe\Wallet\Services\CastService;
use ArsamMe\Wallet\Services\ConsistencyService;
use ArsamMe\Wallet\Services\DatabaseService;
use ArsamMe\Wallet\Services\DispatcherService;
use ArsamMe\Wallet\Services\LockService;
use ArsamMe\Wallet\Services\MathService;
use ArsamMe\Wallet\Services\RegulatorService;
use ArsamMe\Wallet\Services\StateService;
use ArsamMe\Wallet\Services\StorageService;
use ArsamMe\Wallet\Services\TransactionService;
use ArsamMe\Wallet\Services\TransferService;
use ArsamMe\Wallet\Services\WalletService;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

use function dirname;
use function function_exists;

final class LaravelWalletServiceProvider extends ServiceProvider implements DeferrableProvider {
    /**
     * Bootstrap services.
     */
    public function boot(): void {
        Event::listen(TransactionBeginning::class, Listeners\TransactionBeginningListener::class);
        Event::listen(TransactionCommitting::class, Listeners\TransactionCommittingListener::class);
        Event::listen(TransactionCommitted::class, Listeners\TransactionCommittedListener::class);
        Event::listen(TransactionRolledBack::class, Listeners\TransactionRolledBackListener::class);

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->loadMigrationsFrom([dirname(__DIR__).'/database']);

        if (function_exists('config_path')) {
            $this->publishes([
                dirname(__DIR__).'/config/config.php' => config_path('wallet.php'),
            ], 'laravel-wallet-config');
        }

        $this->publishes([
            dirname(__DIR__).'/database/' => database_path('migrations'),
        ], 'laravel-wallet-migrations');
    }

    /**
     * Register services.
     */
    public function register(): void {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/config.php', 'wallet');

        $this->services();
        $this->repositories();
        $this->bindObjects();
    }

    private function services(): void {
        $this->app->alias(StorageService::class, 'wallet.internal.storage');
        $this->app->when(StorageService::class)
            ->needs('$ttl')
            ->giveConfig('wallet.cache.ttl');

        $this->app->when(LockService::class)
            ->needs('$seconds')
            ->giveConfig('wallet.lock.seconds', 1);

        $this->app->when(MathService::class)
            ->needs('$scale')
            ->giveConfig('wallet.math.scale', 64);

        $this->app->when(ConsistencyService::class)
            ->needs('$consistencyChecksumsEnabled')
            ->giveConfig('wallet.consistency.enabled');

        $this->app->when(ConsistencyService::class)
            ->needs('$checksumSecret')
            ->giveConfig('wallet.consistency.secret');

        // bookkeeper service
        $this->app->when(StorageServiceLockDecorator::class)
            ->needs(StorageServiceInterface::class)
            ->give(function () {
                return $this->app->make(
                    'wallet.internal.storage',
                    [
                        'cacheRepository' => $this->app->get(CacheFactory::class)
                            ->store(config('wallet.cache.driver') ?? 'array'),
                    ],
                );
            });

        $this->app->when(BookkeeperService::class)
            ->needs(StorageServiceInterface::class)
            ->give(StorageServiceLockDecorator::class);

        $this->app->when(RegulatorService::class)
            ->needs(StorageServiceInterface::class)
            ->give(function () {
                return $this->app->make(
                    'wallet.internal.storage',
                    [
                        'cacheRepository' => clone $this->app->make(CacheFactory::class)->store('array'),
                    ],
                );
            });

        $this->app->singleton(AtomicServiceInterface::class, AtomicService::class);
        $this->app->singleton(BookkeeperServiceInterface::class, BookkeeperService::class);
        $this->app->singleton(CastServiceInterface::class, CastService::class);
        $this->app->singleton(ConsistencyServiceInterface::class, ConsistencyService::class);
        $this->app->singleton(DatabaseServiceInterface::class, DatabaseService::class);
        $this->app->singleton(DispatcherServiceInterface::class, DispatcherService::class);
        $this->app->singleton(LockServiceInterface::class, LockService::class);
        $this->app->singleton(MathServiceInterface::class, MathService::class);
        $this->app->singleton(RegulatorServiceInterface::class, RegulatorService::class);
        $this->app->singleton(StateServiceInterface::class, StateService::class);
        $this->app->singleton(StorageServiceInterface::class, StorageService::class);
        $this->app->singleton(TransactionServiceInterface::class, TransactionService::class);
        $this->app->singleton(TransferServiceInterface::class, TransferService::class);
        $this->app->singleton(WalletServiceInterface::class, WalletService::class);
    }

    private function repositories(): void {
        $this->app->singleton(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->singleton(TransferRepositoryInterface::class, TransferRepository::class);
        $this->app->singleton(WalletRepositoryInterface::class, WalletRepository::class);
    }

    private function bindObjects(): void {
        $this->app->bind(Wallet::class, config('wallet.wallet.model'));
        $this->app->bind(Transaction::class, config('wallet.transaction.model'));
        $this->app->bind(Transfer::class, config('wallet.transfer.model'));
    }

    public function provides(): array {
        return [
            // Services
            AtomicServiceInterface::class,
            BookkeeperServiceInterface::class,
            CastServiceInterface::class,
            ConsistencyServiceInterface::class,
            DatabaseServiceInterface::class,
            DispatcherServiceInterface::class,
            LockServiceInterface::class,
            MathServiceInterface::class,
            RegulatorServiceInterface::class,
            StateServiceInterface::class,
            StorageServiceInterface::class,
            TransactionServiceInterface::class,
            TransferServiceInterface::class,
            WalletServiceInterface::class,

            // Repositories
            WalletRepositoryInterface::class,
            TransactionRepositoryInterface::class,
            TransferRepositoryInterface::class,
        ];
    }
}
