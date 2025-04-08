<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\ExceptionInterface;
use LogicException;

final class TransactionFailedException extends LogicException implements ExceptionInterface
{
}
