<?php

namespace AliRaghebi\Wallet\Test\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use InvalidArgumentException;

final class PriceNotSetException extends InvalidArgumentException implements ExceptionInterface {}
