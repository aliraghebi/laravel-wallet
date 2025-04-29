<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Test\Unit;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Services\StorageServiceInterface;
use ArsamMe\Wallet\Decorators\StorageServiceLockDecorator;
use ArsamMe\Wallet\Exceptions\RecordNotFoundException;
use ArsamMe\Wallet\Test\TestCase;

/**
 * @internal
 */
final class StorageServiceTest extends TestCase {
    public function test_flush(): void {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::RECORD_NOT_FOUND);
        $storage = app(StorageServiceInterface::class);

        self::assertTrue($storage->sync('hello', 34));
        self::assertTrue($storage->sync('world', 42));
        self::assertSame(42, $storage->get('world'));
        self::assertSame(34, $storage->get('hello'));
        self::assertTrue($storage->flush());

        $storage->get('hello'); // record not found
    }

    public function test_decorator(): void {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::RECORD_NOT_FOUND);
        $storage = app(StorageServiceLockDecorator::class);

        self::assertTrue($storage->sync('hello', 34));
        self::assertTrue($storage->sync('world', 42));
        self::assertSame(42, $storage->get('world'));
        self::assertSame(34, $storage->get('hello'));
        self::assertTrue($storage->flush());

        $storage->get('hello'); // record not found
    }
}
