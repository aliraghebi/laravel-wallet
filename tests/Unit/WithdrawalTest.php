<?php

namespace ArsamMe\Wallet\Test\Unit;

use ArsamMe\Wallet\Exceptions\BalanceIsEmpty;
use ArsamMe\Wallet\Exceptions\InsufficientFunds;
use ArsamMe\Wallet\Test\Models\Transaction;
use ArsamMe\Wallet\Test\TestCase;

/**
 * @internal
 */
final class WithdrawalTest extends TestCase {
    public function test_withdrawal_after_deposit() {
        $user = $this->createUser();
        self::assertSame(0, $user->balance_int);

        $user->deposit(1000);
        self::assertSame(1000, $user->balance_int);

        $user->withdraw(500);
        self::assertSame(500, $user->balance_int);
    }

    public function test_withdraw_float_amount() {
        $user = $this->createUser();
        $wallet = $user->createWallet('btc', decimalPlaces: 20);
        self::assertSame($wallet->decimal_places, 20);

        $wallet->deposit('2');
        self::assertSame(2, $wallet->balance_int);

        $wallet->withdraw('0.0000000002');
        self::assertSame(1.9999999998, $wallet->balance_float);
        self::assertSame('1.99999999980000000000', $wallet->balance);
    }

    public function test_withdrawal_insufficient_balance(): void {
        self::expectException(InsufficientFunds::class);

        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, $user->balance_int);

        $user->withdraw(1100);
    }

    public function test_withdrawal_balance_empty() {
        self::expectException(BalanceIsEmpty::class);

        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, $user->balance_int);

        $user->withdraw(1000);
        self::assertSame(0, $user->balance_int);

        $user->withdraw(1100);
    }

    public function test_withdraw_with_meta() {
        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, $user->balance_int);

        $transaction = $user->withdraw(1000, [
            'settlement_id' => 'AAE-284313',
        ]);

        self::assertTrue($transaction->exists);
        self::assertSame($transaction->meta['settlement_id'], 'AAE-284313');

        $exists = Transaction::where('uuid', $transaction->uuid)->where('meta->settlement_id', 'AAE-284313')->exists();
        self::assertTrue($exists);
    }

    public function test_withdraw_modify_meta(): void {
        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, $user->balance_int);

        $transaction = $user->withdraw(1000);
        self::assertNotNull($transaction);

        $transaction->meta = array_merge($transaction->meta ?? [], [
            'description' => 'Your transaction has been approved',
        ]);

        self::assertTrue($transaction->save());
        self::assertSame('Your transaction has been approved', $transaction->meta['description']);
    }
}
