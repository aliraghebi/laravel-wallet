<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\TestCase;
use PDOException;
use PHPUnit\Framework\MockObject\MockObject;

use function app;

/**
 * @internal
 */
final class BalanceTest extends TestCase {
    public function test_balance_wallet_not_exists(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));

        self::assertSame(0, (int) $buyer->wallet->balance);
        self::assertFalse($buyer->wallet->exists);

        self::assertSame(0, $buyer->wallet->balanceInt);
        self::assertFalse($buyer->wallet->exists);

        self::assertSame(0., (float) $buyer->wallet->balanceFloat);
        self::assertFalse($buyer->wallet->exists);

        self::assertSame(0., $buyer->wallet->balanceFloatNum);
        self::assertFalse($buyer->wallet->exists);
    }

    public function test_deposit_wallet_exists(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $buyer->deposit(1);

        self::assertTrue($buyer->relationLoaded('wallet'));
        self::assertTrue($buyer->wallet->exists);
    }

    public function test_set_name_attribute(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));

        unset($buyer->wallet['slug'], $buyer->wallet['name']);

        $buyer->wallet->name = 'test';
        $buyer->wallet->save();

        $buyer->deposit(1);

        self::assertTrue($buyer->relationLoaded('wallet'));
        self::assertTrue($buyer->wallet->exists);

        self::assertSame('test', $buyer->wallet->name);
        self::assertSame('test', $buyer->wallet->slug);

        self::assertTrue($buyer->wallet->forceDelete());
        self::assertFalse($buyer->wallet->exists);

        $buyer->wallet->name = 'test2';
        $buyer->wallet->save();

        self::assertSame('test2', $buyer->wallet->name);
        self::assertSame('test', $buyer->wallet->slug);

        // exists
        $buyer->wallet->name = 'test3';
        $buyer->wallet->save();

        self::assertSame('test3', $buyer->wallet->name);
        self::assertSame('test', $buyer->wallet->slug);
    }

    public function test_decimal_places(): void {
        config([
            'wallet.wallet.creating' => [
                'decimal_places' => 3,
            ],
        ]);

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $buyer->deposit(1);

        self::assertSame(3, $buyer->wallet->decimal_places);
    }

    /**
     * @see https://github.com/ArsamMe/laravel-wallet/issues/498
     */
    public function test_meta_modify(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $transaction = $buyer->deposit(1000);
        self::assertNotNull($transaction);

        $transaction->meta = array_merge($transaction->meta ?? [], [
            'description' => 'Your transaction has been approved',
        ]);

        self::assertTrue($transaction->save());
    }

    public function test_check_type(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $buyer->deposit(1000);

        self::assertIsString($buyer->balance);
        self::assertIsString($buyer->wallet->balanceFloat);

        self::assertIsInt($buyer->balanceInt);

        self::assertSame('1000', $buyer->balance);
        self::assertSame('10.00', $buyer->wallet->balanceFloat);

        self::assertSame(1000, $buyer->balanceInt);
    }

    public function test_can_withdraw(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertTrue($buyer->canWithdraw(0));

        $buyer->forceWithdraw(1);
        self::assertFalse($buyer->canWithdraw(0));
        self::assertTrue($buyer->canWithdraw(0, true));
    }

    public function test_withdraw_wallet_exists(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        self::assertSame($buyer->balanceInt, 0);
        $buyer->forceWithdraw(1);

        self::assertSame($buyer->balanceInt, -1);
        self::assertTrue($buyer->relationLoaded('wallet'));
        self::assertTrue($buyer->wallet->exists);
        self::assertLessThan(0, $buyer->balanceInt);
    }

    public function test_simple(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();

        self::assertFalse($buyer->relationLoaded('wallet'));
        $wallet = $buyer->wallet;

        self::assertFalse($wallet->exists);
        self::assertSame(0, $wallet->balanceInt);

        $wallet->deposit(1000); // create wallet
        self::assertSame(1000, $wallet->balanceInt);

        $regulator = app(RegulatorServiceInterface::class);
        $result = $regulator->increase($wallet, 100);

        self::assertSame(100, (int) $regulator->diff($wallet));
        self::assertSame(1100, (int) $regulator->amount($wallet));
        self::assertSame(1100, (int) $result);

        self::assertSame(1100, $wallet->balanceInt);
        self::assertTrue($wallet->refreshBalance());

        self::assertSame(0, (int) $regulator->diff($wallet));
        self::assertSame(1000, (int) $regulator->amount($wallet));
        self::assertSame(1000, $wallet->balanceInt);

        $key = $wallet->getKey();
        self::assertTrue($wallet->forceDelete());
        self::assertFalse($wallet->exists);
        self::assertSame($wallet->getKey(), $key);
        $result = app(RegulatorServiceInterface::class)->increase($wallet, 100);

        // databases that do not support fk will not delete data... need to help them
        $wallet->transactions()
            ->where('wallet_id', $key)
            ->delete();

        self::assertFalse($wallet->exists);
        self::assertSame(1100, (int) $result);

        $wallet->refreshBalance(); // automatic create default wallet
        self::assertTrue($wallet->exists);

        self::assertSame(0, $wallet->balanceInt);

        $wallet->deposit(1);
        self::assertSame(1, $wallet->balanceInt);
    }

    public function test_get_balance(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $wallet = $buyer->wallet;

        self::assertFalse($wallet->exists);
        self::assertSame($wallet->balanceInt, 0);
        self::assertFalse($wallet->exists);

        self::assertSame('0', app(BookkeeperServiceInterface::class)->amount($wallet));
    }

    public function test_throw_update(): void {
        $this->expectException(PDOException::class);

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        $wallet = $buyer->wallet;

        self::assertFalse($wallet->exists);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($wallet->exists);

        /** @var MockObject&Wallet $mockQuery */
        $mockQuery = $this->createMock($wallet->newQuery()::class);
        $mockQuery->method('whereKey')
            ->willReturn($mockQuery);
        $mockQuery->method('update')
            ->willThrowException(new PDOException);

        /** @var MockObject&Wallet $mockWallet */
        $mockWallet = $this->createMock(Wallet::class);
        $mockWallet->method('getBalanceAttribute')
            ->willReturn('125');
        $mockWallet->method('newQuery')
            ->willReturn($mockQuery);
        $mockWallet->method('getKey')
            ->willReturn(1);

        $mockWallet->newQuery()
            ->whereKey(1)
            ->update([
                'balance' => 100,
            ]);
    }

    public function test_equal_wallet(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $wallet->deposit(1000);
        self::assertSame(1000, $wallet->balanceInt);
        self::assertSame(1000, $wallet->wallet->balanceInt);
        self::assertSame($wallet->getKey(), $wallet->wallet->getKey());
        self::assertSame($wallet->getKey(), $wallet->wallet->wallet->getKey());
        self::assertSame($wallet->getKey(), $wallet->wallet->wallet->wallet->getKey());
    }
}
