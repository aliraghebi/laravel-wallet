<?php

namespace ArsamMe\Wallet\Test\Infra;

use ArsamMe\Wallet\LaravelWalletServiceProvider;
use ArsamMe\Wallet\Test\Infra\PackageModels\Transaction;
use ArsamMe\Wallet\Test\Infra\PackageModels\Transfer;
use ArsamMe\Wallet\Test\Infra\PackageModels\Wallet;
use ArsamMe\Wallet\Test\Infra\Services\MyExchangeService;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * @internal
 */
abstract class TestCase extends OrchestraTestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        DB::transactionLevel() && DB::rollBack();
    }

    final public function expectExceptionMessageStrict(mixed $message): void {
        assert(is_string($message));

        $this->expectExceptionMessageMatches("~^{$message}$~");
    }

    /**
     * @param  Application  $app
     * @return non-empty-array<int, string>
     */
    final protected function getPackageProviders($app): array {
        // Bind eloquent models to IoC container
        $app['config']->set('wallet.services.exchange', MyExchangeService::class);
        $app['config']->set('wallet.transaction.model', Transaction::class);
        $app['config']->set('wallet.transfer.model', Transfer::class);
        $app['config']->set('wallet.wallet.model', Wallet::class);

        return [LaravelWalletServiceProvider::class, TestServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    final protected function getEnvironmentSetUp($app): void {
        /** @var Repository $config */
        $config = $app['config'];

        // database
        $config->set('database.connections.testing.prefix', 'test_');
        $config->set('database.connections.pgsql.prefix', 'test_');
        $config->set('database.connections.mysql.prefix', 'test_');
        $config->set('database.connections.mariadb.prefix', 'test_');
        $config->set('database.connections.mariadb.port', 3307);

        // new table name's
        $config->set('wallet.transaction.table', 'transactions');
        $config->set('wallet.transfer.table', 'transfers');
        $config->set('wallet.wallet.table', 'wallets');
    }
}
