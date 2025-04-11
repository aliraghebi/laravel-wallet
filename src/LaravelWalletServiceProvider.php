<?php

namespace ArsamMe\Wallet;

use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\LockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Services\AtomicService;
use ArsamMe\Wallet\Services\DatabaseService;
use ArsamMe\Wallet\Services\LockService;
use ArsamMe\Wallet\Services\MathService;
use ArsamMe\Wallet\Services\WalletService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use function dirname;
use function function_exists;

final class LaravelWalletServiceProvider extends ServiceProvider implements DeferrableProvider {
    /**
     * Bootstrap services.
     */
    public function boot(): void {
        //        if (!$this->app->runningInConsole()) {
        //            return;
        //        }

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
        $this->app->when(LockService::class)
            ->needs('$seconds')
            ->giveConfig('wallet.lock.seconds', 1);

        $this->app->when(MathService::class)
            ->needs('$scale')
            ->giveConfig('wallet.math.scale', 24);

        $this->app->when(WalletService::class)
            ->needs('$walletSecret')
            ->giveConfig('wallet.secret');

        $this->app->singleton(MathServiceInterface::class, MathService::class);
        $this->app->singleton(LockServiceInterface::class, LockService::class);
        $this->app->singleton(AtomicServiceInterface::class, AtomicService::class);
        $this->app->singleton(WalletServiceInterface::class, WalletService::class);
        $this->app->singleton(DatabaseServiceInterface::class, DatabaseService::class);
    }

    public function provides(): array {
        return [
            MathServiceInterface::class,
            LockServiceInterface::class,
            AtomicServiceInterface::class,
            WalletServiceInterface::class,
            DatabaseServiceInterface::class,
        ];
    }
}
