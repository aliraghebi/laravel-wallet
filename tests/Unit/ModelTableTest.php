<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class ModelTableTest extends TestCase {
    public function test_wallet_table_name(): void {
        $user = $this->createUser();
        self::assertSame('wallet', $user->wallet->getTable());
    }

    public function test_transaction_table_name(): void {
        $user = $this->createUser();
        $transaction = $user->deposit(100);
        self::assertSame('transaction', $transaction->getTable());
    }

    public function test_transfer_table_name(): void {
        [$user1, $user2] = $this->createUser(2);
        $user1->deposit(1000);
        $transfer = $user1->transfer($user2, 1000);
        self::assertSame('transfer', $transfer->getTable());
    }
}
