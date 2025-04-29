<?php

namespace ArsamMe\Wallet\Test\Unit;

use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Test\Models\Transaction;
use ArsamMe\Wallet\Test\Models\Transfer;
use ArsamMe\Wallet\Test\Models\Wallet;
use ArsamMe\Wallet\Test\TestCase;

/**
 * @internal
 */
final class SingletonTest extends TestCase {
    public function test_math_interface(): void {
        self::assertSame($this->getRefId(MathServiceInterface::class), $this->getRefId(MathServiceInterface::class));
    }

    public function test_transaction(): void {
        self::assertNotSame($this->getRefId(Transaction::class), $this->getRefId(Transaction::class));
    }

    public function test_transfer(): void {
        self::assertNotSame($this->getRefId(Transfer::class), $this->getRefId(Transfer::class));
    }

    public function test_wallet(): void {
        self::assertNotSame($this->getRefId(Wallet::class), $this->getRefId(Wallet::class));
    }

    public function test_database_service(): void {
        self::assertSame(
            $this->getRefId(DatabaseServiceInterface::class),
            $this->getRefId(DatabaseServiceInterface::class)
        );
    }

    private function getRefId(string $object): string {
        return spl_object_hash(app($object));
    }
}
