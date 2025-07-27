<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Exceptions\BalanceIsEmptyException;
use AliRaghebi\Wallet\Exceptions\InsufficientFundsException;
use AliRaghebi\Wallet\Exceptions\InvalidAmountException;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class TransferTest extends TestCase {
    public function test_transfer() {
        [$user1, $user2] = $this->createUser(2);

        // Create default wallets with 10 decimal places
        $user1->createWallet();
        $user2->createWallet();

        // Wallets should be empty
        self::assertSame(0, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        // Deposit 1000 to user1
        $user1->deposit(1000);
        self::assertSame(1000, (int) $user1->balance);
        // User2's wallet should still be empty
        self::assertSame(0, (int) $user2->balance);

        // Transfer float amount to user2
        $transfer = $user1->transfer($user2, 100.0000000001);
        self::assertInstanceOf(Transfer::class, $transfer);
        self::assertSame(899.9999999999, (float) $user1->balance);
        self::assertSame(100.0000000001, (float) $user2->balance);
    }

    public function test_transfer_to_same_user() {
        $user = $this->createUser();

        $user->deposit(1000);
        self::assertSame(1000, (int) $user->balance);

        $transfer = $user->transfer($user, 900, 100);
        self::assertTrue($transfer->exists);
        self::assertTrue($transfer->withdrawal->exists);
        self::assertTrue($transfer->deposit->exists);

        self::assertSame(900, (int) $user->balance);
    }

    public function test_transfer_wallet_creation() {
        [$user1, $user2] = $this->createUser(2);

        self::assertSame(0, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        self::assertFalse($user1->wallet->exists);
        self::assertFalse($user2->wallet->exists);

        $user1->deposit(1);
        self::assertSame(1, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        self::assertTrue($user1->wallet->exists);
        self::assertFalse($user2->wallet->exists);

        $user1->transfer($user2, 0.5);
        self::assertSame(0.5, (float) $user1->balance);
        self::assertSame(0.5, (float) $user2->balance);

        self::assertTrue($user1->wallet->exists);
        self::assertTrue($user2->wallet->exists);
    }

    public function test_transfer_with_fee() {
        [$user1, $user2] = $this->createUser(2);

        // Create default wallets with 10 decimal places
        $user1->createWallet();
        $user2->createWallet();

        // Wallets should be empty
        self::assertSame(0, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        // Deposit 1000 to user1
        $user1->deposit(1000);
        self::assertSame(1000, (int) $user1->balance);
        // User2's wallet should still be empty
        self::assertSame(0, (int) $user2->balance);

        // Transfer float amount to user2
        $transfer = $user1->transfer($user2, 100.0000000001, fee: 0.0000000002);
        self::assertInstanceOf(Transfer::class, $transfer);
        self::assertSame(899.9999999997, (float) $user1->balance);
        self::assertSame(100.0000000001, (float) $user2->balance);
    }

    public function test_transfer_with_meta_and_uuid() {
        [$user1, $user2] = $this->createUser(2);

        // Wallets should be empty
        self::assertSame(0, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        // Deposit 1000 to user1
        $user1->deposit(1);
        self::assertSame(1, (int) $user1->balance);
        // User2's wallet should still be empty
        self::assertSame(0, (int) $user2->balance);

        $identityService = app(IdentifierFactoryServiceInterface::class);
        $transferUuid = $identityService->generate();
        $withdrawalUuid = $identityService->generate();
        $depositUuid = $identityService->generate();

        // Transfer float amount to user2
        $extra = new TransferExtra(
            uuid: $transferUuid,
            meta: ['description' => 'Transfer between users'],
            depositExtra: new TransactionExtra(
                uuid: $depositUuid,
                meta: ['from' => $user1->name]
            ),
            withdrawalExtra: new TransactionExtra(
                uuid: $withdrawalUuid,
                meta: ['reason' => 'debt']
            ),
        );

        $transfer = $user1->transfer($user2, 0.5, extra: $extra);
        self::assertTrue($transfer->exists);

        // assert uuids
        self::assertSame($transferUuid, $transfer->uuid);
        self::assertSame($withdrawalUuid, $transfer->withdrawal->uuid);
        self::assertSame($depositUuid, $transfer->deposit->uuid);

        // assert meta
        self::assertSame('Transfer between users', $transfer->meta['description']);
        self::assertSame($user1->name, $transfer->deposit->meta['from']);
        self::assertSame('debt', $transfer->withdrawal->meta['reason']);

        // check exists in db
        $transferExists = $user1->wallet->transfers()
            ->where('uuid', $transferUuid)
            ->exists();
        self::assertTrue($transferExists);

        $receivedTransferExists = $user2->wallet->receivedTransfers()
            ->where('uuid', $transferUuid)
            ->exists();
        self::assertTrue($receivedTransferExists);

        $withdrawalExists = $user1->wallet->transactions()
            ->where('uuid', $withdrawalUuid)
            ->where('meta->reason', 'debt')
            ->exists();
        self::assertTrue($withdrawalExists);

        $depositExists = $user2->wallet->transactions()
            ->where('uuid', $depositUuid)
            ->where('meta->from', $user1->name)
            ->exists();
        self::assertTrue($depositExists);
    }

    public function test_transfer_balance_empty() {
        self::expectException(BalanceIsEmptyException::class);

        [$user1, $user2] = $this->createUser(2);

        // Wallets should be empty
        self::assertSame(0, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        $user1->transfer($user2, 1);
    }

    public function test_transfer_insufficient_balance() {
        self::expectException(InsufficientFundsException::class);

        [$user1, $user2] = $this->createUser(2);

        // Wallets should be empty
        self::assertSame(0, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        $user1->deposit(1000);
        self::assertSame(1000, (int) $user1->balance);
        self::assertSame(0, (int) $user2->balance);

        $user1->transfer($user2, 2000);
    }
}
