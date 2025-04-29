<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Test\Unit;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Exceptions\TransactionFailedException;
use ArsamMe\Wallet\Test\TestCase;
use RuntimeException;

/**
 * @internal
 */
final class DatabaseServiceTest extends TestCase {
    public function test_check_code(): void {
        $this->expectException(TransactionFailedException::class);
        $this->expectExceptionCode(ExceptionInterface::TRANSACTION_FAILED);
        $this->expectExceptionMessage('Transaction failed. Message: hello');

        app(DatabaseServiceInterface::class)->transaction(static function (): never {
            throw new RuntimeException('hello');
        });
    }
}
