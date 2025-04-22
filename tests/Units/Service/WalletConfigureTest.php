<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\Test\Infra\TestCase;
use ArsamMe\Wallet\WalletConfigure;

/**
 * @internal
 */
final class WalletConfigureTest extends TestCase {
    public function test_ignore_migrations(): void {
        self::assertTrue(WalletConfigure::isRunsMigrations());

        WalletConfigure::ignoreMigrations();
        self::assertFalse(WalletConfigure::isRunsMigrations());

        WalletConfigure::reset();
        self::assertTrue(WalletConfigure::isRunsMigrations());
    }
}
