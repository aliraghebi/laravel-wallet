<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\Internal\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Internal\Exceptions\TransactionFailedException;
use ArsamMe\Wallet\Internal\Service\DatabaseServiceInterface;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class DatabaseTest extends TestCase {
    /**
     * @throws ExceptionInterface
     */
    public function test_check_code(): void {
        $this->expectException(TransactionFailedException::class);
        $this->expectExceptionCode(ExceptionInterface::TRANSACTION_FAILED);
        $this->expectExceptionMessage('Transaction failed. Message: hello');

        app(DatabaseServiceInterface::class)->transaction(static function (): never {
            throw new \RuntimeException('hello');
        });
    }
}
