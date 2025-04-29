<?php

namespace ArsamMe\Wallet\Test\Unit;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Services\ClockServiceInterface;
use ArsamMe\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use ArsamMe\Wallet\Events\TransactionCreatedEvent;
use ArsamMe\Wallet\Events\WalletCreatedEvent;
use ArsamMe\Wallet\Events\WalletUpdatedEvent;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Test\Exceptions\UnknownEventException;
use ArsamMe\Wallet\Test\Listeners\TransactionCreatedThrowListener;
use ArsamMe\Wallet\Test\Listeners\WalletCreatedThrowListener;
use ArsamMe\Wallet\Test\Listeners\WalletUpdatedThrowIdListener;
use ArsamMe\Wallet\Test\Listeners\WalletUpdatedThrowUuidListener;
use ArsamMe\Wallet\Test\Services\ClockFakeService;
use ArsamMe\Wallet\Test\TestCase;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 */
final class EventTest extends TestCase {
    public function test_balance_updated_throw_uuid_listener(): void {
        Event::listen(WalletUpdatedEvent::class, WalletUpdatedThrowUuidListener::class);

        $user = $this->createUser();
        self::assertSame(0, $user->wallet->balance_int);
        self::assertTrue($user->wallet->saveQuietly()); // create without event

        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($user->wallet->uuid);
        $this->expectExceptionCode(123 + $user->wallet->getKey());

        $user->deposit(123);
    }

    public function test_balance_updated_throw_id_listener(): void {
        Event::listen(WalletUpdatedEvent::class, WalletUpdatedThrowIdListener::class);

        $user = $this->createUser();
        self::assertSame(0, $user->wallet->balance_int); // no create wallet

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
        self::assertSame(0, $user->wallet->balance_int);

        $createdAt = app(ClockServiceInterface::class)->now()->format(DateTimeInterface::ATOM);

        $message = hash('sha256', Transaction::TYPE_DEPOSIT.$createdAt);

        // unit
        $this->expectException(UnknownEventException::class);
        $this->expectExceptionMessage($message);

        $user->deposit(100);
    }
}
