<?php

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use InvalidArgumentException;

final class InvalidFeeException extends InvalidArgumentException implements ExceptionInterface {}
