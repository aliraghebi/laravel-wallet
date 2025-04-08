<?php

namespace src;

use ArsamMe\Wallet\External\Api\TransactionQueryHandler;
use ArsamMe\Wallet\External\Api\TransactionQueryHandlerInterface;
use ArsamMe\Wallet\External\Api\TransferQueryHandler;
use ArsamMe\Wallet\External\Api\TransferQueryHandlerInterface;
use ArsamMe\Wallet\Internal;
use ArsamMe\Wallet\Internal\Assembler\AvailabilityDtoAssembler;
use ArsamMe\Wallet\Internal\Assembler\AvailabilityDtoAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\BalanceUpdatedEventAssembler;
use ArsamMe\Wallet\Internal\Assembler\BalanceUpdatedEventAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\ExtraDtoAssembler;
use ArsamMe\Wallet\Internal\Assembler\ExtraDtoAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\OptionDtoAssembler;
use ArsamMe\Wallet\Internal\Assembler\OptionDtoAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\TransactionCreatedEventAssembler;
use ArsamMe\Wallet\Internal\Assembler\TransactionCreatedEventAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\TransactionDtoAssembler;
use ArsamMe\Wallet\Internal\Assembler\TransactionDtoAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\TransactionQueryAssembler;
use ArsamMe\Wallet\Internal\Assembler\TransactionQueryAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\TransferDtoAssembler;
use ArsamMe\Wallet\Internal\Assembler\TransferDtoAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\TransferLazyDtoAssembler;
use ArsamMe\Wallet\Internal\Assembler\TransferLazyDtoAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\TransferQueryAssembler;
use ArsamMe\Wallet\Internal\Assembler\TransferQueryAssemblerInterface;
use ArsamMe\Wallet\Internal\Assembler\WalletCreatedEventAssembler;
use ArsamMe\Wallet\Internal\Assembler\WalletCreatedEventAssemblerInterface;
use ArsamMe\Wallet\Internal\Decorator\StorageServiceLockDecorator;
use ArsamMe\Wallet\Internal\Events\BalanceUpdatedEvent;
use ArsamMe\Wallet\Internal\Events\BalanceUpdatedEventInterface;
use ArsamMe\Wallet\Internal\Events\TransactionCreatedEvent;
use ArsamMe\Wallet\Internal\Events\TransactionCreatedEventInterface;
use ArsamMe\Wallet\Internal\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Internal\Events\WalletCreatedEventInterface;
use ArsamMe\Wallet\Internal\Repository\TransactionRepository;
use ArsamMe\Wallet\Internal\Repository\TransactionRepositoryInterface;
use ArsamMe\Wallet\Internal\Repository\TransferRepository;
use ArsamMe\Wallet\Internal\Repository\TransferRepositoryInterface;
use ArsamMe\Wallet\Internal\Repository\WalletRepository;
use ArsamMe\Wallet\Internal\Repository\WalletRepositoryInterface;
use ArsamMe\Wallet\Internal\Service\ClockService;
use ArsamMe\Wallet\Internal\Service\ClockServiceInterface;
use ArsamMe\Wallet\Internal\Service\ConnectionService;
use ArsamMe\Wallet\Internal\Service\ConnectionServiceInterface;
use ArsamMe\Wallet\Internal\Service\DatabaseService;
use ArsamMe\Wallet\Internal\Service\DatabaseServiceInterface;
use ArsamMe\Wallet\Internal\Service\DispatcherService;
use ArsamMe\Wallet\Internal\Service\DispatcherServiceInterface;
use ArsamMe\Wallet\Internal\Service\IdentifierFactoryService;
use ArsamMe\Wallet\Internal\Service\IdentifierFactoryServiceInterface;
use ArsamMe\Wallet\Internal\Service\JsonService;
use ArsamMe\Wallet\Internal\Service\JsonServiceInterface;
use ArsamMe\Wallet\Internal\Service\LockService;
use ArsamMe\Wallet\Internal\Service\LockServiceInterface;
use ArsamMe\Wallet\Internal\Service\MathService;
use ArsamMe\Wallet\Internal\Service\MathServiceInterface;
use ArsamMe\Wallet\Internal\Service\StateService;
use ArsamMe\Wallet\Internal\Service\StateServiceInterface;
use ArsamMe\Wallet\Internal\Service\StorageService;
use ArsamMe\Wallet\Internal\Service\StorageServiceInterface;
use ArsamMe\Wallet\Internal\Service\TranslatorService;
use ArsamMe\Wallet\Internal\Service\TranslatorServiceInterface;
use ArsamMe\Wallet\Internal\Service\UuidFactoryService;
use ArsamMe\Wallet\Internal\Service\UuidFactoryServiceInterface;
use ArsamMe\Wallet\Internal\Transform\TransactionDtoTransformer;
use ArsamMe\Wallet\Internal\Transform\TransactionDtoTransformerInterface;
use ArsamMe\Wallet\Internal\Transform\TransferDtoTransformer;
use ArsamMe\Wallet\Internal\Transform\TransferDtoTransformerInterface;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Services\AssistantService;
use ArsamMe\Wallet\Services\AssistantServiceInterface;
use ArsamMe\Wallet\Services\AtmService;
use ArsamMe\Wallet\Services\AtmServiceInterface;
use ArsamMe\Wallet\Services\AtomicService;
use ArsamMe\Wallet\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Services\BasketService;
use ArsamMe\Wallet\Services\BasketServiceInterface;
use ArsamMe\Wallet\Services\BookkeeperService;
use ArsamMe\Wallet\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Services\CastService;
use ArsamMe\Wallet\Services\CastServiceInterface;
use ArsamMe\Wallet\Services\ConsistencyService;
use ArsamMe\Wallet\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Services\DiscountService;
use ArsamMe\Wallet\Services\DiscountServiceInterface;
use ArsamMe\Wallet\Services\EagerLoaderService;
use ArsamMe\Wallet\Services\EagerLoaderServiceInterface;
use ArsamMe\Wallet\Services\ExchangeService;
use ArsamMe\Wallet\Services\ExchangeServiceInterface;
use ArsamMe\Wallet\Services\FormatterService;
use ArsamMe\Wallet\Services\FormatterServiceInterface;
use ArsamMe\Wallet\Services\PrepareService;
use ArsamMe\Wallet\Services\PrepareServiceInterface;
use ArsamMe\Wallet\Services\PurchaseService;
use ArsamMe\Wallet\Services\PurchaseServiceInterface;
use ArsamMe\Wallet\Services\RegulatorService;
use ArsamMe\Wallet\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Services\TaxService;
use ArsamMe\Wallet\Services\TaxServiceInterface;
use ArsamMe\Wallet\Services\TransactionService;
use ArsamMe\Wallet\Services\TransactionServiceInterface;
use ArsamMe\Wallet\Services\TransferService;
use ArsamMe\Wallet\Services\TransferServiceInterface;
use ArsamMe\Wallet\Services\WalletService;
use ArsamMe\Wallet\Services\WalletServiceInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

use function ArsamMe\Wallet\config_path;
use function ArsamMe\Wallet\database_path;
use function config;
use function dirname;
use function function_exists;

final class WalletServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'wallet');

        if (! $this->app->runningInConsole()) {
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
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/config.php', 'wallet');

        /**
         * @var array{
         *     internal?: array<class-string|null>,
         *     services?: array<class-string|null>,
         *     cache?: array{driver: string|null},
         *     repositories?: array<class-string|null>,
         *     transformers?: array<class-string|null>,
         *     assemblers?: array<class-string|null>,
         *     events?: array<class-string|null>,
         *     transaction?: array{model?: class-string|null},
         *     transfer?: array{model?: class-string|null},
         *     wallet?: array{model?: class-string|null},
         * } $configure
         */
        $configure = config('wallet', []);

        $this->internal($configure['internal'] ?? []);
        $this->services($configure['services'] ?? [], $configure['cache'] ?? []);

        $this->repositories($configure['repositories'] ?? []);
        $this->transformers($configure['transformers'] ?? []);
        $this->assemblers($configure['assemblers'] ?? []);
        $this->events($configure['events'] ?? []);

        $this->bindObjects($configure);
    }

    /**
     * @return class-string[]
     */
    public function provides(): array
    {
        return array_merge(
            $this->internalProviders(),
            $this->servicesProviders(),
            $this->repositoriesProviders(),
            $this->transformersProviders(),
            $this->assemblersProviders(),
            $this->eventsProviders(),
            $this->bindObjectsProviders(),
        );
    }

    /**
     * @param  array<class-string|null>  $configure
     */
    private function repositories(array $configure): void
    {
        $this->app->singleton(
            TransactionRepositoryInterface::class,
            $configure['transaction'] ?? TransactionRepository::class
        );

        $this->app->singleton(
            TransferRepositoryInterface::class,
            $configure['transfer'] ?? TransferRepository::class
        );

        $this->app->singleton(WalletRepositoryInterface::class, $configure['wallet'] ?? WalletRepository::class);
    }

    /**
     * @param  array<class-string|null>  $configure
     */
    private function internal(array $configure): void
    {
        $this->app->alias($configure['storage'] ?? StorageService::class, 'wallet.internal.storage');
        $this->app->when($configure['storage'] ?? StorageService::class)
            ->needs('$ttl')
            ->giveConfig('wallet.cache.ttl');

        $this->app->singleton(ClockServiceInterface::class, $configure['clock'] ?? ClockService::class);
        $this->app->singleton(ConnectionServiceInterface::class, $configure['connection'] ?? ConnectionService::class);
        $this->app->singleton(DatabaseServiceInterface::class, $configure['database'] ?? DatabaseService::class);
        $this->app->singleton(DispatcherServiceInterface::class, $configure['dispatcher'] ?? DispatcherService::class);
        $this->app->singleton(JsonServiceInterface::class, $configure['json'] ?? JsonService::class);

        $this->app->when($configure['lock'] ?? LockService::class)
            ->needs('$seconds')
            ->giveConfig('wallet.lock.seconds', 1);

        $this->app->singleton(LockServiceInterface::class, $configure['lock'] ?? LockService::class);

        $this->app->when($configure['math'] ?? MathService::class)
            ->needs('$scale')
            ->giveConfig('wallet.math.scale', 64);

        $this->app->singleton(MathServiceInterface::class, $configure['math'] ?? MathService::class);
        $this->app->singleton(StateServiceInterface::class, $configure['state'] ?? StateService::class);
        $this->app->singleton(TranslatorServiceInterface::class, $configure['translator'] ?? TranslatorService::class);
        $this->app->singleton(UuidFactoryServiceInterface::class, $configure['uuid'] ?? UuidFactoryService::class);
        $this->app->singleton(
            IdentifierFactoryServiceInterface::class,
            $configure['identifier'] ?? IdentifierFactoryService::class
        );
    }

    /**
     * @param  array<class-string|null>  $configure
     * @param  array{driver?: string|null}  $cache
     */
    private function services(array $configure, array $cache): void
    {
        $this->app->singleton(AssistantServiceInterface::class, $configure['assistant'] ?? AssistantService::class);
        $this->app->singleton(AtmServiceInterface::class, $configure['atm'] ?? AtmService::class);
        $this->app->singleton(AtomicServiceInterface::class, $configure['atomic'] ?? AtomicService::class);
        $this->app->singleton(BasketServiceInterface::class, $configure['basket'] ?? BasketService::class);
        $this->app->singleton(CastServiceInterface::class, $configure['cast'] ?? CastService::class);
        $this->app->singleton(
            ConsistencyServiceInterface::class,
            $configure['consistency'] ?? ConsistencyService::class
        );
        $this->app->singleton(DiscountServiceInterface::class, $configure['discount'] ?? DiscountService::class);
        $this->app->singleton(
            EagerLoaderServiceInterface::class,
            $configure['eager_loader'] ?? EagerLoaderService::class
        );
        $this->app->singleton(ExchangeServiceInterface::class, $configure['exchange'] ?? ExchangeService::class);
        $this->app->singleton(FormatterServiceInterface::class, $configure['formatter'] ?? FormatterService::class);
        $this->app->singleton(PrepareServiceInterface::class, $configure['prepare'] ?? PrepareService::class);
        $this->app->singleton(PurchaseServiceInterface::class, $configure['purchase'] ?? PurchaseService::class);
        $this->app->singleton(TaxServiceInterface::class, $configure['tax'] ?? TaxService::class);
        $this->app->singleton(
            TransactionServiceInterface::class,
            $configure['transaction'] ?? TransactionService::class
        );
        $this->app->singleton(TransferServiceInterface::class, $configure['transfer'] ?? TransferService::class);
        $this->app->singleton(WalletServiceInterface::class, $configure['wallet'] ?? WalletService::class);

        // bookkeepper service
        $this->app->when(StorageServiceLockDecorator::class)
            ->needs(StorageServiceInterface::class)
            ->give(function () use ($cache) {
                return $this->app->make(
                    'wallet.internal.storage',
                    [
                        'cacheRepository' => $this->app->get(CacheFactory::class)
                            ->store($cache['driver'] ?? 'array'),
                    ],
                );
            });

        $this->app->when($configure['bookkeeper'] ?? BookkeeperService::class)
            ->needs(StorageServiceInterface::class)
            ->give(StorageServiceLockDecorator::class);

        $this->app->singleton(BookkeeperServiceInterface::class, $configure['bookkeeper'] ?? BookkeeperService::class);

        // regulator service
        $this->app->when($configure['regulator'] ?? RegulatorService::class)
            ->needs(StorageServiceInterface::class)
            ->give(function () {
                return $this->app->make(
                    'wallet.internal.storage',
                    [
                        'cacheRepository' => clone $this->app->make(CacheFactory::class)
                            ->store('array'),
                    ],
                );
            });

        $this->app->singleton(RegulatorServiceInterface::class, $configure['regulator'] ?? RegulatorService::class);
    }

    /**
     * @param  array<class-string|null>  $configure
     */
    private function assemblers(array $configure): void
    {
        $this->app->singleton(
            AvailabilityDtoAssemblerInterface::class,
            $configure['availability'] ?? AvailabilityDtoAssembler::class
        );

        $this->app->singleton(
            BalanceUpdatedEventAssemblerInterface::class,
            $configure['balance_updated_event'] ?? BalanceUpdatedEventAssembler::class
        );

        $this->app->singleton(ExtraDtoAssemblerInterface::class, $configure['extra'] ?? ExtraDtoAssembler::class);

        $this->app->singleton(
            OptionDtoAssemblerInterface::class,
            $configure['option'] ?? OptionDtoAssembler::class
        );

        $this->app->singleton(
            TransactionDtoAssemblerInterface::class,
            $configure['transaction'] ?? TransactionDtoAssembler::class
        );

        $this->app->singleton(
            TransferLazyDtoAssemblerInterface::class,
            $configure['transfer_lazy'] ?? TransferLazyDtoAssembler::class
        );

        $this->app->singleton(
            TransferDtoAssemblerInterface::class,
            $configure['transfer'] ?? TransferDtoAssembler::class
        );

        $this->app->singleton(
            TransactionQueryAssemblerInterface::class,
            $configure['transaction_query'] ?? TransactionQueryAssembler::class
        );

        $this->app->singleton(
            TransferQueryAssemblerInterface::class,
            $configure['transfer_query'] ?? TransferQueryAssembler::class
        );

        $this->app->singleton(
            WalletCreatedEventAssemblerInterface::class,
            $configure['wallet_created_event'] ?? WalletCreatedEventAssembler::class
        );

        $this->app->singleton(
            TransactionCreatedEventAssemblerInterface::class,
            $configure['transaction_created_event'] ?? TransactionCreatedEventAssembler::class
        );
    }

    /**
     * @param  array<class-string|null>  $configure
     */
    private function transformers(array $configure): void
    {
        $this->app->singleton(
            TransactionDtoTransformerInterface::class,
            $configure['transaction'] ?? TransactionDtoTransformer::class
        );

        $this->app->singleton(
            TransferDtoTransformerInterface::class,
            $configure['transfer'] ?? TransferDtoTransformer::class
        );
    }

    /**
     * @param  array<class-string|null>  $configure
     */
    private function events(array $configure): void
    {
        $this->app->bind(
            BalanceUpdatedEventInterface::class,
            $configure['balance_updated'] ?? BalanceUpdatedEvent::class
        );

        $this->app->bind(
            WalletCreatedEventInterface::class,
            $configure['wallet_created'] ?? WalletCreatedEvent::class
        );

        $this->app->bind(
            TransactionCreatedEventInterface::class,
            $configure['transaction_created'] ?? TransactionCreatedEvent::class
        );
    }

    /**
     * @param array{
     *     transaction?: array{model?: class-string|null},
     *     transfer?: array{model?: class-string|null},
     *     wallet?: array{model?: class-string|null},
     * } $configure
     */
    private function bindObjects(array $configure): void
    {
        $this->app->bind(Transaction::class, $configure['transaction']['model'] ?? null);
        $this->app->bind(Transfer::class, $configure['transfer']['model'] ?? null);
        $this->app->bind(Wallet::class, $configure['wallet']['model'] ?? null);

        // api
        $this->app->bind(TransactionQueryHandlerInterface::class, TransactionQueryHandler::class);
        $this->app->bind(TransferQueryHandlerInterface::class, TransferQueryHandler::class);
    }

    /**
     * @return class-string[]
     */
    private function internalProviders(): array
    {
        return [
            ClockServiceInterface::class,
            ConnectionServiceInterface::class,
            DatabaseServiceInterface::class,
            DispatcherServiceInterface::class,
            JsonServiceInterface::class,
            LockServiceInterface::class,
            MathServiceInterface::class,
            StateServiceInterface::class,
            TranslatorServiceInterface::class,
            UuidFactoryServiceInterface::class,
            IdentifierFactoryServiceInterface::class,
        ];
    }

    /**
     * @return class-string[]
     */
    private function servicesProviders(): array
    {
        return [
            AssistantServiceInterface::class,
            AtmServiceInterface::class,
            AtomicServiceInterface::class,
            BasketServiceInterface::class,
            CastServiceInterface::class,
            ConsistencyServiceInterface::class,
            DiscountServiceInterface::class,
            EagerLoaderServiceInterface::class,
            ExchangeServiceInterface::class,
            FormatterServiceInterface::class,
            PrepareServiceInterface::class,
            PurchaseServiceInterface::class,
            TaxServiceInterface::class,
            TransactionServiceInterface::class,
            TransferServiceInterface::class,
            WalletServiceInterface::class,

            BookkeeperServiceInterface::class,
            RegulatorServiceInterface::class,
        ];
    }

    /**
     * @return class-string[]
     */
    private function repositoriesProviders(): array
    {
        return [
            TransactionRepositoryInterface::class,
            TransferRepositoryInterface::class,
            WalletRepositoryInterface::class,
        ];
    }

    /**
     * @return class-string[]
     */
    private function transformersProviders(): array
    {
        return [
            AvailabilityDtoAssemblerInterface::class,
            BalanceUpdatedEventAssemblerInterface::class,
            ExtraDtoAssemblerInterface::class,
            OptionDtoAssemblerInterface::class,
            TransactionDtoAssemblerInterface::class,
            TransferLazyDtoAssemblerInterface::class,
            TransferDtoAssemblerInterface::class,
            TransactionQueryAssemblerInterface::class,
            TransferQueryAssemblerInterface::class,
            WalletCreatedEventAssemblerInterface::class,
            TransactionCreatedEventAssemblerInterface::class,
        ];
    }

    /**
     * @return class-string[]
     */
    private function assemblersProviders(): array
    {
        return [TransactionDtoTransformerInterface::class, TransferDtoTransformerInterface::class];
    }

    /**
     * @return class-string[]
     */
    private function eventsProviders(): array
    {
        return [
            BalanceUpdatedEventInterface::class,
            WalletCreatedEventInterface::class,
            TransactionCreatedEventInterface::class,
        ];
    }

    /**
     * @return class-string[]
     */
    private function bindObjectsProviders(): array
    {
        return [TransactionQueryHandlerInterface::class, TransferQueryHandlerInterface::class];
    }
}
