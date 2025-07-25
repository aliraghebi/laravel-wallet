<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\LaravelWalletServiceProvider;
use AliRaghebi\Wallet\Test\TestCase;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * @internal
 */
final class DeferrableServiceProviderTest extends TestCase {
    public function test_check_deferrable_provider(): void {
        $walletServiceProvider = app()->resolveProvider(LaravelWalletServiceProvider::class);

        self::assertInstanceOf(DeferrableProvider::class, $walletServiceProvider);
        self::assertNotEmpty($walletServiceProvider->provides());
    }
}
