<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Test\Infra\Factories\UserMultiFactory;
use ArsamMe\Wallet\Test\Infra\Models\UserMulti;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class TransactionAmountFloatAccessorTest extends TestCase {
    /**
     * @see https://github.com/ArsamMe/laravel-wallet/pull/533
     */
    public function test_transaction_amount_float_accessor(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();

        // two decimal
        $twoDecimalWallet = $user->createWallet([
            'name' => '2 Floating point',
            'slug' => '2-floating-point',
            'decimal_places' => 2,
        ]);

        $amountTwoDecimal = 1.11;
        $twoDecimalTransaction = $twoDecimalWallet->depositFloat($amountTwoDecimal);

        self::assertNotNull($twoDecimalTransaction);
        self::assertSame(
            (string) $amountTwoDecimal,
            $twoDecimalTransaction->amountFloat,
            'amount float is same decimal places'
        );

        // four decimal
        $fourDecimalWallet = $user->createWallet([
            'name' => '4 Floating point',
            'slug' => '4-floating-point',
            'decimal_places' => 4,
        ]);

        $amountFourDecimal = 1.1111;
        $fourDecimalTransaction = $fourDecimalWallet->depositFloat($amountFourDecimal);

        self::assertNotNull($fourDecimalTransaction);
        self::assertSame(
            (string) $amountFourDecimal,
            $fourDecimalTransaction->amountFloat,
            'amount float is same decimal places'
        );
    }
}
