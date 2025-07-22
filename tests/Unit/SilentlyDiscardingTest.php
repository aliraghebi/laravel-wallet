<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Test\Services\ClockFakeService;
use AliRaghebi\Wallet\Test\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
final class SilentlyDiscardingTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Model::preventSilentlyDiscardingAttributes();
    }

    protected function tearDown(): void {
        parent::tearDown();
        Model::preventSilentlyDiscardingAttributes(false);
    }

    public function test_deposit_silently_discarding(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));
        $user->deposit(1);

        self::assertTrue($user->relationLoaded('wallet'));
        self::assertTrue($user->wallet->exists);
        self::assertSame(1, $user->balance_int);
    }

    public function test_transfer_silently_discarding(): void {
        [$first, $second] = $this->createUser(2);
        self::assertNotSame($first->getKey(), $second->getKey());

        self::assertNotNull($first->deposit(1000));
        self::assertSame(1000, $first->balanceInt);

        self::assertNotNull($first->transfer($second, 500));
        self::assertSame(500, $first->balanceInt);
        self::assertSame(500, $second->balanceInt);
    }

    public function test_multi_wallet_silently_discarding(): void {
        $this->app?->bind(ClockServiceInterface::class, ClockFakeService::class);

        $user = $this->createUser();
        $dateTime = app(ClockServiceInterface::class)->now();

        $wallet = $user->wallet;
        $wallet->name = 'Test';
        $wallet->created_at = $dateTime;
        $wallet->updated_at = $dateTime;
        $wallet->save();

        self::assertCount(1, $user->wallets);
        self::assertSame($dateTime->getTimestamp(), $wallet->created_at->getTimestamp());
        self::assertSame($dateTime->getTimestamp(), $wallet->updated_at->getTimestamp());
    }
}
