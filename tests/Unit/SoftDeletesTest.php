<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Test\Models\User;
use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class SoftDeletesTest extends TestCase {
    public function test_default_wallet_soft_delete(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));
        self::assertFalse($user->wallet->exists);

        $user->deposit(1);

        $oldWallet = $user->wallet;

        self::assertTrue($user->wallet->exists);
        self::assertTrue($user->wallet->delete());
        self::assertNotNull($user->wallet->deleted_at);

        $user = User::query()->find($user->getKey());

        $user->deposit(2);

        self::assertSame($user->wallet->getKey(), $oldWallet->getKey());

        self::assertSame(3, $oldWallet->balance_int);
        self::assertSame(3, $user->balance_int);
    }

    public function test_default_wallet_force_delete(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));
        self::assertFalse($user->wallet->exists);

        $user->deposit(1);

        $oldWallet = $user->wallet;

        self::assertTrue($user->wallet->exists);
        self::assertTrue($user->wallet->forceDelete());
        self::assertFalse($user->wallet->exists);

        $user = User::query()->find($user->getKey());

        $user->deposit(2);

        self::assertNotSame($user->wallet->getKey(), $oldWallet->getKey());

        self::assertSame(1, $oldWallet->balance_int);
        self::assertSame(2, $user->balance_int);
    }

    public function test_transaction_delete(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));
        self::assertFalse($user->wallet->exists);

        $transaction = $user->deposit(1);

        self::assertTrue($user->wallet->exists);
        self::assertSame(1, $user->balance_int);

        self::assertTrue($transaction->delete());

        self::assertNotNull($transaction->deleted_at);
    }

    public function test_transfer_delete(): void {
        [$user1, $user2] = $this->createUser(2);

        self::assertFalse($user1->relationLoaded('wallet'));
        self::assertFalse($user1->wallet->exists);

        self::assertFalse($user2->relationLoaded('wallet'));
        self::assertFalse($user2->wallet->exists);

        $user1->deposit(100);
        self::assertSame(100, $user1->balanceInt);
        self::assertSame(0, $user2->balanceInt);

        $transfer = $user1->transfer($user2, 100);

        self::assertNotNull($transfer);
        self::assertSame(100, $transfer->deposit->amount_int);
        self::assertSame(-100, $transfer->withdrawal->amount_int);

        self::assertSame(0, $user1->balanceInt);
        self::assertSame(100, $user2->balanceInt);

        self::assertTrue($transfer->delete());
        self::assertNotNull($transfer->deleted_at);
    }
}
