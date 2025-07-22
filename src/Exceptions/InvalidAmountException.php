<?php

namespace AliRaghebi\Wallet\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use InvalidArgumentException;

final class InvalidAmountException extends InvalidArgumentException implements ExceptionInterface {}
