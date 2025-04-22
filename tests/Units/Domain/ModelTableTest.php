<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Test\Infra\Factories\ManagerFactory;
use ArsamMe\Wallet\Test\Infra\Factories\UserFactory;
use ArsamMe\Wallet\Test\Infra\Models\Manager;
use ArsamMe\Wallet\Test\Infra\Models\User;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class ModelTableTest extends TestCase {
    public function test_wallet_table_name(): void {
        /** @var User $user */
        $user = UserFactory::new()->create();
        self::assertSame('wallet', $user->wallet->getTable());
    }

    public function test_transaction_table_name(): void {
        /** @var User $user */
        $user = UserFactory::new()->create();
        $transaction = $user->deposit(100);
        self::assertSame('transaction', $transaction->getTable());
    }

    public function test_transfer_table_name(): void {
        /**
         * @var User $user1
         * @var User $user2
         */
        [$user1, $user2] = UserFactory::times(2)->create();
        $user1->deposit(1000);
        $transfer = $user1->transfer($user2, 1000);
        self::assertSame('transfer', $transfer->getTable());

        /** @var Manager $manager */
        $manager = ManagerFactory::new()->create();
        $user2->transfer($manager, 1000);
        self::assertSame(1000, (int) $manager->balance);
    }
}
