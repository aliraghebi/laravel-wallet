<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Data\WalletStateData;
use AliRaghebi\Wallet\Decorators\StorageServiceLockDecorator;
use AliRaghebi\Wallet\Exceptions\RecordNotFoundException;
use AliRaghebi\Wallet\Services\StorageService;
use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class StorageServiceTest extends TestCase {
    public function test_flush(): void {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::RECORD_NOT_FOUND);
        $storage = app(StorageService::class);

        $state = new WalletStateData('123.456', '987.654', 10);

        self::assertTrue($storage->sync('my-key', $state));
        self::assertSame($state->balance, $storage->get('my-key')->balance);
        self::assertSame($state->frozenAmount, $storage->get('my-key')->frozenAmount);
        self::assertTrue($storage->flush());

        $storage->get('my-key'); // record not found
    }

    public function test_decorator(): void {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::RECORD_NOT_FOUND);
        $storage = app(StorageServiceLockDecorator::class);

        $state = new WalletStateData('123.456', '987.654', 10);

        self::assertTrue($storage->sync('my-key', $state));
        self::assertSame($state->balance, $storage->get('my-key')->balance);
        self::assertSame($state->frozenAmount, $storage->get('my-key')->frozenAmount);
        self::assertTrue($storage->flush());

        $storage->get('my-key'); // record not found
    }
}
