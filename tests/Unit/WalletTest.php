<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\WalletServiceInterface;
use AliRaghebi\Wallet\Exceptions\ModelNotFoundException;
use AliRaghebi\Wallet\Facades\LaravelWallet;
use AliRaghebi\Wallet\Test\Models\MyWallet;
use AliRaghebi\Wallet\Test\Models\Wallet;
use AliRaghebi\Wallet\Test\TestCase;
use Illuminate\Support\Facades\Config;
use PDOException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class WalletTest extends TestCase {
    public function test_find_by(): void {
        $user = $this->createUser();

        $uuidFactoryService = app(IdentifierFactoryServiceInterface::class);
        $walletService = app(WalletServiceInterface::class);

        $uuid = $uuidFactoryService->generate();

        self::assertNull($walletService->findBySlug($user, 'default'));
        self::assertNull($walletService->findByUuid($uuid));
        self::assertNull($walletService->findById(-1));

        $user->wallet->uuid = $uuid;
        $user->deposit(100);

        self::assertNotNull($walletService->findBySlug($user, 'default'));
        self::assertNotNull($walletService->findByUuid($uuid));
        self::assertNotNull($walletService->findById($user->wallet->getKey()));
    }

    public function test_get_by_slug(): void {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::MODEL_NOT_FOUND);

        $user = $this->createUser();
        $walletService = app(WalletServiceInterface::class);

        $walletService->findOrFailBySlug($user, 'default');
    }

    public function test_get_by_id(): void {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::MODEL_NOT_FOUND);

        app(WalletServiceInterface::class)->findOrFailById(-1);
    }

    public function test_create_wallet_with_uuid(): void {
        $user = $this->createUser();

        $uuidFactoryService = app(IdentifierFactoryServiceInterface::class);

        /** @var string[] $uuids */
        $uuids = array_map(static fn () => $uuidFactoryService->generate(), range(1, 10));

        foreach ($uuids as $uuid) {
            $user->createWallet(name: md5($uuid), uuid: $uuid);
        }

        self::assertSame(10, $user->wallets()->count());
        self::assertSame(10, $user->wallets()->whereIn('uuid', $uuids)->count());
    }

    public function test_get_by_uuid(): void {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::MODEL_NOT_FOUND);

        $uuidFactoryService = app(IdentifierFactoryServiceInterface::class);

        app(WalletServiceInterface::class)->findOrFailByUuid($uuidFactoryService->generate());
    }

    public function test_balance_wallet_not_exists(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));

        self::assertSame(0, (int) $user->wallet->balance);
        self::assertFalse($user->wallet->exists);

        self::assertSame(0, (int) $user->wallet->balance);
        self::assertFalse($user->wallet->exists);

        self::assertSame(0., (float) $user->wallet->balance);
        self::assertFalse($user->wallet->exists);
    }

    public function test_create_wallet() {
        $user = $this->createUser();
        self::assertFalse($user->hasWallet('btc'));

        $wallet = $user->createWallet(slug: 'btc');
        self::assertTrue($user->hasWallet('btc'));

        self::assertSame($wallet->getKey(), $user->getWallet('btc')->getKey());
    }

    public function test_create_multi_wallets(): void {
        $user = $this->createUser();
        $slugs = ['dollar', 'euro', 'ruble'];

        foreach ($slugs as $slug) {
            self::assertNull($user->getWallet($slug));
            $wallet = $user->createWallet(ucfirst($slug), $slug);

            self::assertNotNull($wallet);
            self::assertSame($slug, $wallet->slug);

            self::assertTrue((bool) $wallet->deposit(1000));
        }

        self::assertEqualsCanonicalizing($slugs, $user->wallets->pluck('slug')->toArray());

        self::assertCount(count($slugs), $user->wallets()->get());

        foreach ($user->wallets()->get() as $wallet) {
            self::assertSame(1000, (int) $wallet->balance);
            self::assertContains($wallet->slug, $slugs);
        }
    }

    public function test_get_wallet_or_fail_error(): void {
        $user = $this->createUser();
        self::assertSame(0, (int) $user->balance); // createWallet

        $this->expectException(ModelNotFoundException::class);

        $user->findOrFailWallet(Config::string('wallet.wallet.default.slug', 'default'));
    }

    public function test_get_wallet_or_fail_success(): void {
        $user = $this->createUser();
        self::assertSame(0, (int) $user->balance); // createWallet
        $uuid = $user->wallet->uuid;

        $user->deposit(1);

        $walletResult = $user->findOrFailWallet(Config::string('wallet.wallet.default.slug', 'default'));

        self::assertSame($uuid, $walletResult->uuid);
    }

    public function test_set_name_attribute(): void {
        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));

        unset($user->wallet['slug'], $user->wallet['name']);

        $user->wallet->name = 'test';
        $user->wallet->save();

        self::assertTrue($user->relationLoaded('wallet'));
        self::assertTrue($user->wallet->exists);

        self::assertSame('test', $user->wallet->name);
        self::assertSame('test', $user->wallet->slug);

        self::assertTrue($user->wallet->forceDelete());
        self::assertFalse($user->wallet->exists);

        $user->wallet->name = 'test2';
        $user->wallet->save();

        self::assertSame('test2', $user->wallet->name);
        self::assertSame('test', $user->wallet->slug);

        // exists
        $user->wallet->name = 'test3';
        $user->wallet->save();

        self::assertSame('test3', $user->wallet->name);
        self::assertSame('test', $user->wallet->slug);
    }

    public function test_check_type(): void {
        $user = $this->createUser();
        $wallet = $user->createWallet('btc');
        $wallet->deposit(1000);

        self::assertIsString($wallet->balance);
        self::assertIsFloat((float) $wallet->balance);
        self::assertIsInt((int) $wallet->balance);

        self::assertSame('1000', $wallet->balance);
        self::assertSame(1000., (float) $wallet->balance);
        self::assertSame(1000, (int) $wallet->balance);
    }

    public function test_can_withdraw(): void {
        $user = $this->createUser();
        self::assertTrue($user->canWithdraw(0));
        self::assertFalse($user->canWithdraw(1));

        $user->deposit(1000);
        self::assertTrue($user->canWithdraw(1000));
        self::assertFalse($user->canWithdraw(1001));

        $user->withdraw(500);
        self::assertFalse($user->canWithdraw(1000));
    }

    public function test_throw_update(): void {
        $this->expectException(PDOException::class);

        $user = $this->createUser();
        self::assertFalse($user->relationLoaded('wallet'));
        $wallet = $user->wallet;

        self::assertSame(0, (int) $wallet->balance);
        self::assertFalse($wallet->exists);

        /** @var MockObject&Wallet $mockQuery */
        $mockQuery = $this->createMock($wallet->newQuery()::class);
        $mockQuery->method('whereKey')->willReturn($mockQuery);
        $mockQuery->method('update')->willThrowException(new PDOException);

        /** @var MockObject&Wallet $mockWallet */
        $mockWallet = $this->createMock(Wallet::class);
        $mockWallet->method('getBalanceAttribute')->willReturn('125');
        $mockWallet->method('newQuery')->willReturn($mockQuery);
        $mockWallet->method('getKey')->willReturn(1);

        $mockWallet->newQuery()->whereKey(1)->update([
            'balance' => 100,
        ]);
    }

    public function test_equal_wallet(): void {
        $user = $this->createUser();
        $wallet = $user->wallet;

        self::assertSame(0, (int) $wallet->balance);

        $wallet->deposit(1000);
        self::assertSame(1000, (int) $wallet->balance);
        self::assertSame(1000, (int) $user->wallet->balance);
        self::assertSame($wallet->getKey(), $user->wallet->getKey());
    }

    public function test_extend_model(): void {
        config([
            'wallet.wallet.model' => MyWallet::class,
        ]);

        $user = $this->createUser();

        /** @var MyWallet $wallet */
        $wallet = $user->wallet;

        self::assertSame('hello world', $wallet->helloWorld());
    }
}
