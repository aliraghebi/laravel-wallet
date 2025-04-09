<?php
declare(strict_types=1);

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\ExceptionInterface;
use InvalidArgumentException;

final class TransactionRollbackException extends InvalidArgumentException implements ExceptionInterface
{
    public function __construct(
        private readonly mixed $result
    )
    {
        parent::__construct();
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
