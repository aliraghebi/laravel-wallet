<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Services\DatabaseServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\MathServiceInterface;
use AliRaghebi\Wallet\Test\Models\Transaction;
use AliRaghebi\Wallet\Test\Models\Transfer;
use AliRaghebi\Wallet\Test\Models\Wallet;
use AliRaghebi\Wallet\Test\TestCase;

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
