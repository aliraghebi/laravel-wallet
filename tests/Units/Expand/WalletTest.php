<?php

namespace ArsamMe\Wallet\Test\Units\Expand;

use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\PackageModels\MyWallet;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class WalletTest extends TestCase {
    public function test_add_method(): void {
        config([
            'wallet.wallet.model' => MyWallet::class,
        ]);

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();

        /** @var MyWallet $wallet */
        $wallet = $buyer->wallet;

        self::assertSame('hello world', $wallet->helloWorld());
    }
}
