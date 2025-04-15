<?php

namespace ArsamMe\Wallet;

use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Decorators\StorageServiceLockDecorator;
use ArsamMe\Wallet\Services\AtomicService;
use ArsamMe\Wallet\Services\BookkeeperService;
use ArsamMe\Wallet\Services\CastService;
use ArsamMe\Wallet\Services\ConsistencyService;
use ArsamMe\Wallet\Services\DatabaseService;
use ArsamMe\Wallet\Services\LockService;
use ArsamMe\Wallet\Services\MathService;
use ArsamMe\Wallet\Services\RegulatorService;
use ArsamMe\Wallet\Services\StorageService;
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

        $this->app->when(WalletService::class)
            ->needs('$walletSecret')
            ->giveConfig('wallet.secret');

        // bookkeepper service
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
        $this->app->singleton(LockServiceInterface::class, LockService::class);
        $this->app->singleton(MathServiceInterface::class, MathService::class);
        $this->app->singleton(RegulatorServiceInterface::class, RegulatorService::class);
        $this->app->singleton(StorageServiceInterface::class, StorageService::class);
        $this->app->singleton(WalletServiceInterface::class, WalletService::class);
    }

    public function provides(): array {
        return [
            AtomicServiceInterface::class,
            BookkeeperServiceInterface::class,
            CastServiceInterface::class,
            ConsistencyServiceInterface::class,
            DatabaseServiceInterface::class,
            LockServiceInterface::class,
            MathServiceInterface::class,
            RegulatorServiceInterface::class,
            StorageServiceInterface::class,
            WalletServiceInterface::class,
        ];
    }
}
