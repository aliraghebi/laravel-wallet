<?php

namespace AliRaghebi\Wallet\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use InvalidArgumentException;

final class InvalidFeeException extends InvalidArgumentException implements ExceptionInterface {}
