<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\Internal\Decorator\StorageServiceLockDecorator;
use ArsamMe\Wallet\Internal\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Internal\Exceptions\RecordNotFoundException;
use ArsamMe\Wallet\Internal\Service\StorageService;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class StorageTest extends TestCase {
    public function test_flush(): void {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::RECORD_NOT_FOUND);
        $storage = app(StorageService::class);

        self::assertTrue($storage->sync('hello', 34));
        self::assertTrue($storage->sync('world', 42));
        self::assertSame('42', $storage->get('world'));
        self::assertSame('34', $storage->get('hello'));
        self::assertTrue($storage->flush());

        $storage->get('hello'); // record not found
    }

    public function test_decorator(): void {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionCode(ExceptionInterface::RECORD_NOT_FOUND);
        $storage = app(StorageServiceLockDecorator::class);

        self::assertTrue($storage->sync('hello', 34));
        self::assertTrue($storage->sync('world', 42));
        self::assertSame('42', $storage->get('world'));
        self::assertSame('34', $storage->get('hello'));
        self::assertTrue($storage->flush());

        $storage->get('hello'); // record not found
    }

    public function test_increase_decorator(): void {
        $storage = app(StorageServiceLockDecorator::class);

        $storage->multiSync([
            'hello' => 34,
        ]);

        self::assertSame('34', $storage->get('hello'));
        self::assertSame('42', $storage->increase('hello', 8));
    }
}
