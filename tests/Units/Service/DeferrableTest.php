<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\LaravelWalletServiceProvider;
use ArsamMe\Wallet\Test\Infra\TestCase;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * @internal
 */
final class DeferrableTest extends TestCase {
    public function test_check_deferrable_provider(): void {
        $walletServiceProvider = app()
            ->resolveProvider(LaravelWalletServiceProvider::class);

        self::assertInstanceOf(DeferrableProvider::class, $walletServiceProvider);
        self::assertNotEmpty($walletServiceProvider->provides());
    }
}
