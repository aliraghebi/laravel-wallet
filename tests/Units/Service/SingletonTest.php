<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\Internal\Service\DatabaseServiceInterface;
use ArsamMe\Wallet\Internal\Service\MathServiceInterface;
use ArsamMe\Wallet\Objects\Cart;
use ArsamMe\Wallet\Test\Infra\PackageModels\Transaction;
use ArsamMe\Wallet\Test\Infra\PackageModels\Transfer;
use ArsamMe\Wallet\Test\Infra\PackageModels\Wallet;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class SingletonTest extends TestCase {
    public function test_cart(): void {
        self::assertNotSame($this->getRefId(Cart::class), $this->getRefId(Cart::class));
    }

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
