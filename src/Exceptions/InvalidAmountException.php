<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use InvalidArgumentException;

final class InvalidAmountException extends InvalidArgumentException implements ExceptionInterface {}
