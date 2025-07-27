<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Services\AtomicServiceInterface;
use AliRaghebi\Wallet\Test\Models\User;
use AliRaghebi\Wallet\Test\TestCase;
use Exception;
use Throwable;

/**
 * @internal
 */
final class AtomicServiceTest extends TestCase {
    public function test_block(): void {
        $atomic = app(AtomicServiceInterface::class);

        [$user1, $user2] = $this->createUser(2);

        $user1->deposit(1000);

        $atomic->blocks(
            [$user1->wallet, $user2->wallet],
            fn () => collect([
                fn () => $user1->transfer($user2, 500),
                fn () => $user1->transfer($user2, 500),
                fn () => $user2->transfer($user1, 500),
            ])
                ->map(fn ($fx) => $fx()),
        );

        self::assertSame(1, $user2->wallet->transfers()->count());
        self::assertSame(2, $user2->wallet->receivedTransfers()->count());
        self::assertSame(2, $user1->wallet->transfers()->count());
        self::assertSame(1, $user1->wallet->receivedTransfers()->count());
        self::assertSame(3, $user2->wallet->transactions()->count());
        self::assertSame(4, $user1->wallet->transactions()->count());

        self::assertSame(500, (int) $user1->balance);
        self::assertSame(500, (int) $user2->balance);
    }

    public function test_block_iter3(): void {
        $atomicService = app(AtomicServiceInterface::class);

        $user = $this->createUser();
        $iterations = 3;

        self::assertSame(0, (int) $user->balance);

        for ($i = 1; $i <= $iterations; $i++) {
            $atomicService->block($user, function () use ($user) {
                $user->deposit(5000);
                $user->withdraw(1000);
                $user->withdraw(1000);
                $user->withdraw(1000);
            });
        }

        self::assertSame($iterations * 2000, (int) $user->balance);
    }

    /**
     * Tests the rollback functionality of the AtomicService.
     *
     * This test creates a new Buyer and deposits 1000 units into their wallet. Then, it attempts to
     * withdraw 3000 units from the wallet within an atomic block. Since there are not enough funds,
     * an exception is thrown. The test then checks that the balance of the wallet has not changed.
     */
    public function test_rollback(): void {
        // Create a new instance of the AtomicService
        $atomic = app(AtomicServiceInterface::class);

        // Create a new Buyer and deposit 1000 units into their wallet
        $user = $this->createUser();
        $user->deposit(1000);

        // Check that the balance of the wallet is 1000 units
        $this->assertSame(1000, (int) $user->balance);

        try {
            // Start an atomic block and attempt to withdraw 3000 units from the wallet
            $atomic->block($user, function () use ($user) {
                // Deposit 5000 units into the wallet
                $user->deposit(5000);
                // Withdraw 1000 units from the wallet
                $user->withdraw(1000);
                // Withdraw 1000 units from the wallet
                $user->withdraw(1000);
                // Withdraw 1000 units from the wallet
                $user->withdraw(1000);

                // Throw an exception to simulate an error
                throw new Exception;
            });

            // This should not be reached
            self::fail(); // check
        } catch (Throwable $e) {
            // Intentionally left empty
        }

        // Retrieve the Buyer from the database and check that the balance is still 1000 units
        $userFromDb = User::find($user->getKey());

        // Check that the balance of the wallet is 1000 units
        $this->assertSame(1000, (int) $userFromDb->balance);
        // Check that the balance of the wallet is 1000 units
        $this->assertSame(1000, (int) $user->balance);
    }

    public function test_multi_function() {
        $user = $this->createUser();
        $wallet = $user->wallet;

        $atomic = app(AtomicServiceInterface::class);

        $atomic->block($wallet, function () use ($wallet) {
            $wallet->deposit(10000);
            self::assertSame((int) $wallet->balance, 10000);
            self::assertSame((int) $wallet->available_balance, 10000);

            $wallet->freeze(5000);
            self::assertSame((int) $wallet->frozen_amount, 5000);
            self::assertSame((int) $wallet->available_balance, 5000);

            $wallet->unFreeze(2000);
            self::assertSame((int) $wallet->frozen_amount, 3000);
            self::assertSame((int) $wallet->available_balance, 7000);

            $wallet->withdraw(5000);
            self::assertSame((int) $wallet->balance, 5000);
            self::assertSame((int) $wallet->available_balance, 2000);
        });


        self::assertSame((int) $wallet->balance, 5000);
        self::assertSame((int) $wallet->frozen_amount, 3000);
        self::assertSame((int) $wallet->available_balance, 2000);
    }
}
