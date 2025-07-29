<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Events\TransactionCreatedEvent;
use AliRaghebi\Wallet\Events\WalletCreatedEvent;
use AliRaghebi\Wallet\Events\WalletUpdatedEvent;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Test\Exceptions\UnknownEventException;
use AliRaghebi\Wallet\Test\Listeners\TransactionCreatedThrowListener;
use AliRaghebi\Wallet\Test\Listeners\WalletCreatedThrowListener;
use AliRaghebi\Wallet\Test\Listeners\WalletUpdatedThrowIdListener;
use AliRaghebi\Wallet\Test\Listeners\WalletUpdatedThrowUuidListener;
use AliRaghebi\Wallet\Test\Services\ClockFakeService;
use AliRaghebi\Wallet\Test\TestCase;
use DateTimeInterface;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 */
final class EventTest extends TestCase {
    public function test_balance_updated_throw_uuid_listener(): void {
        Event::listen(WalletUpdatedEvent::class, WalletUpdatedThrowUuidListener::class);

        $user = $this->createUser();
        self::assertSame(0, (int) $user->wallet->balance);
        self::assertTrue($user->wallet->saveQuietly()); // create without event

        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($user->wallet->uuid);
        $this->expectExceptionCode(123 + $user->wallet->getKey());

        $user->deposit(123);
    }

    public function test_balance_updated_throw_id_listener(): void {
        Event::listen(WalletUpdatedEvent::class, WalletUpdatedThrowIdListener::class);

        $user = $this->createUser();
        self::assertSame(0, (int) $user->wallet->balance); // no create wallet

        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($user->wallet->uuid);
        $this->expectExceptionCode(456);

        $user->deposit(456);
    }

    public function test_wallet_created_throw_listener(): void {
        Event::listen(WalletCreatedEvent::class, WalletCreatedThrowListener::class);

        $user = $this->createUser();

        $uuidFactoryService = app(IdentifierFactoryServiceInterface::class);
        $user->wallet->uuid = $uuidFactoryService->generate();

        $holderType = $user->getMorphClass();
        $uuid = $user->wallet->uuid;

        $message = hash('sha256', $holderType.$uuid);

        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($message);

        $user->withdraw(0);
    }

    public function test_multi_wallet_created_throw_listener(): void {
        Event::listen(WalletCreatedEvent::class, WalletCreatedThrowListener::class);

        $user = $this->createUser();

        $uuidFactoryService = app(IdentifierFactoryServiceInterface::class);
        $uuid = $uuidFactoryService->generate();

        $holderType = $user->getMorphClass();

        $message = hash('sha256', $holderType.$uuid);

        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($message);

        $user->createWallet(
            name: 'Bitcoin',
            slug: 'btc',
            uuid: $uuid,
        );
    }

    public function test_transaction_created_throw_listener(): void {
        $this->app?->bind(ClockServiceInterface::class, ClockFakeService::class);

        Event::listen(TransactionCreatedEvent::class, TransactionCreatedThrowListener::class);

        $user = $this->createUser();
        self::assertSame(0, (int) $user->wallet->balance);

        $createdAt = app(ClockServiceInterface::class)->now()->format(DateTimeInterface::ATOM);

        $message = hash('sha256', Transaction::TYPE_DEPOSIT.$createdAt);

        // unit
        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($message);

        $user->deposit(100);
    }
}
