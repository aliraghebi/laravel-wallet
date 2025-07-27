<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Test\Models\Transaction;
use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class DepositTest extends TestCase {
    public function test_deposit() {
        $user = $this->createUser();
        self::assertSame(0, (int) $user->balance);

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $user->deposit(100);
        self::assertSame(1100, (int) $user->balance);
    }

    public function test_wallet_creation_with_deposit(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));
        $user->deposit(1);

        self::assertTrue($user->relationLoaded('wallet'));
        self::assertTrue($user->wallet->exists);
    }

    public function test_deposit_float_amount() {
        $user = $this->createUser();
        $wallet = $user->createWallet('btc');

        $wallet->deposit('1.1234567890');
        self::assertSame(1.1234567890, (float) $wallet->balance);
        self::assertSame('1.123456789', $wallet->balance);
    }

    public function test_deposit_with_meta() {
        $user = $this->createUser();
        $transaction = $user->deposit(
            1000,
            new TransactionExtra(
                meta: [
                    'product_id' => 10009000,
                ]
            )
        );

        self::assertTrue($transaction->exists);
        self::assertSame($transaction->meta['product_id'], 10009000);

        $exists = Transaction::where('uuid', $transaction->uuid)->where('meta->product_id', 10009000)->exists();
        self::assertTrue($exists);
    }

    public function test_deposit_modify_meta(): void {
        $user = $this->createUser();
        $transaction = $user->deposit(1000);
        self::assertNotNull($transaction);

        $transaction->meta = array_merge($transaction->meta ?? [], [
            'description' => 'Your transaction has been approved',
        ]);

        self::assertTrue($transaction->save());
        self::assertSame('Your transaction has been approved', $transaction->meta['description']);
    }
}
