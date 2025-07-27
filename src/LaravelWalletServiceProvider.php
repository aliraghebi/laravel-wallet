<?php

namespace AliRaghebi\Wallet;

use AliRaghebi\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Repositories\TransferRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Services\AtomicServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\BookkeeperServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DatabaseServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DispatcherServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\JsonServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\LockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\RegulatorServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\StateServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\StorageServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransactionServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransferServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\WalletServiceInterface;
use AliRaghebi\Wallet\Decorators\StorageServiceLockDecorator;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet;
use AliRaghebi\Wallet\Repositories\TransactionRepository;
use AliRaghebi\Wallet\Repositories\TransferRepository;
use AliRaghebi\Wallet\Repositories\WalletRepository;
use AliRaghebi\Wallet\Services\AtomicService;
use AliRaghebi\Wallet\Services\BookkeeperService;
use AliRaghebi\Wallet\Services\CastService;
use AliRaghebi\Wallet\Services\ClockService;
use AliRaghebi\Wallet\Services\ConsistencyService;
use AliRaghebi\Wallet\Services\DatabaseService;
use AliRaghebi\Wallet\Services\DispatcherService;
use AliRaghebi\Wallet\Services\IdentifierFactoryService;
use AliRaghebi\Wallet\Services\JsonService;
use AliRaghebi\Wallet\Services\LockService;
use AliRaghebi\Wallet\Services\RegulatorService;
use AliRaghebi\Wallet\Services\StateService;
use AliRaghebi\Wallet\Services\StorageService;
use AliRaghebi\Wallet\Services\TransactionService;
use AliRaghebi\Wallet\Services\TransferService;
use AliRaghebi\Wallet\Services\WalletService;
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
        $this->app->alias(StorageService::class, 'wallet.services.storage');
        $this->app->when(StorageService::class)
            ->needs('$ttl')
            ->giveConfig('wallet.cache.ttl');

        $this->app->when(LockService::class)
            ->needs('$seconds')
            ->giveConfig('wallet.lock.seconds', 1);

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
                    'wallet.services.storage',
                    [
                        'cacheRepository' => $this->app->get(CacheFactory::class)->store(config('wallet.cache.driver') ?? 'array'),
                        'prefix' => 'bookkeeper_sg::',
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
                    'wallet.services.storage',
                    [
                        'cacheRepository' => clone $this->app->make(CacheFactory::class)->store('array'),
                        'prefix' => 'regulator_sg::',
                    ],
                );
            });

        $this->app->singleton(AtomicServiceInterface::class, AtomicService::class);
        $this->app->singleton(BookkeeperServiceInterface::class, BookkeeperService::class);
        $this->app->singleton(CastServiceInterface::class, CastService::class);
        $this->app->singleton(ClockServiceInterface::class, ClockService::class);
        $this->app->singleton(ConsistencyServiceInterface::class, ConsistencyService::class);
        $this->app->singleton(DatabaseServiceInterface::class, DatabaseService::class);
        $this->app->singleton(DispatcherServiceInterface::class, DispatcherService::class);
        $this->app->singleton(IdentifierFactoryServiceInterface::class, IdentifierFactoryService::class);
        $this->app->singleton(JsonServiceInterface::class, JsonService::class);
        $this->app->singleton(LockServiceInterface::class, LockService::class);
        $this->app->singleton(RegulatorServiceInterface::class, RegulatorService::class);
        $this->app->singleton(StateServiceInterface::class, StateService::class);
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
            ClockServiceInterface::class,
            ConsistencyServiceInterface::class,
            DatabaseServiceInterface::class,
            DispatcherServiceInterface::class,
            IdentifierFactoryServiceInterface::class,
            JsonServiceInterface::class,
            LockServiceInterface::class,
            RegulatorServiceInterface::class,
            StateServiceInterface::class,
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
