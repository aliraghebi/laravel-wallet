<?php

namespace ArsamMe\Wallet\Test\Infra\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use InvalidArgumentException;

final class PriceNotSetException extends InvalidArgumentException implements ExceptionInterface {}
