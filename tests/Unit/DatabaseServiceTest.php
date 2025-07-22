<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Services\DatabaseServiceInterface;
use AliRaghebi\Wallet\Exceptions\TransactionFailedException;
use AliRaghebi\Wallet\Test\TestCase;
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
