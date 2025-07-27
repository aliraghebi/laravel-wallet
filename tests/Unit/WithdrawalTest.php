<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Exceptions\BalanceIsEmptyException;
use AliRaghebi\Wallet\Exceptions\InsufficientFundsException;
use AliRaghebi\Wallet\Test\Models\Transaction;
use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class WithdrawalTest extends TestCase {
    public function test_withdrawal_after_deposit() {
        $user = $this->createUser();
        self::assertSame(0, (int) $user->balance);

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $user->withdraw(500);
        self::assertSame(500, (int) $user->balance);
    }

    public function test_withdraw_float_amount() {
        $user = $this->createUser();
        $wallet = $user->createWallet('btc');

        $wallet->deposit('2');
        self::assertSame(2, (int) $wallet->balance);

        $wallet->withdraw('0.0000000002');
        self::assertSame(1.9999999998, (float) $wallet->balance);
        self::assertSame('1.9999999998', $wallet->balance);
    }

    public function test_withdrawal_insufficient_balance(): void {
        self::expectException(InsufficientFundsException::class);

        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $user->withdraw(1100);
    }

    public function test_withdrawal_balance_empty() {
        self::expectException(BalanceIsEmptyException::class);

        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $user->withdraw(1000);
        self::assertSame(0, (int) $user->balance);

        $user->withdraw(1100);
    }

    public function test_withdraw_with_meta() {
        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $transaction = $user->withdraw(
            1000,
            new TransactionExtra(
                meta: [
                    'settlement_id' => 'AAE-284313',
                ]
            )
        );

        self::assertTrue($transaction->exists);
        self::assertSame($transaction->meta['settlement_id'], 'AAE-284313');

        $exists = Transaction::where('uuid', $transaction->uuid)->where('meta->settlement_id', 'AAE-284313')->exists();
        self::assertTrue($exists);
    }

    public function test_withdraw_modify_meta(): void {
        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $transaction = $user->withdraw(1000);
        self::assertNotNull($transaction);

        $transaction->meta = array_merge($transaction->meta ?? [], [
            'description' => 'Your transaction has been approved',
        ]);

        self::assertTrue($transaction->save());
        self::assertSame('Your transaction has been approved', $transaction->meta['description']);
    }
}
