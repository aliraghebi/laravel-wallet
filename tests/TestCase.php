<?php

namespace ArsamMe\Wallet\Test;

use ArsamMe\Wallet\LaravelWalletServiceProvider;
use ArsamMe\Wallet\Test\Factories\UserFactory;
use ArsamMe\Wallet\Test\Models\Transaction;
use ArsamMe\Wallet\Test\Models\Transfer;
use ArsamMe\Wallet\Test\Models\User;
use ArsamMe\Wallet\Test\Models\Wallet;
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
        $config->set('database.connections.testing.prefix', 'tests');
        $config->set('database.connections.pgsql.prefix', 'tests');
        $config->set('database.connections.mysql.prefix', 'tests');
        $config->set('database.connections.mariadb.prefix', 'tests');
        $config->set('database.connections.mariadb.port', 3307);

        // new table name's
        $config->set('wallet.transaction.table', 'transaction');
        $config->set('wallet.transfer.table', 'transfer');
        $config->set('wallet.wallet.table', 'wallet');
    }

    /**
     * @return User|array<User>
     */
    protected function createUser(int $count = 1): User|array {
        if ($count == 1) {
            return UserFactory::new()->create();
        }

        return UserFactory::times($count)->create()->all();
    }
}
