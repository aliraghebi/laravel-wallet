<?php

namespace ArsamMe\Wallet\Test\Infra\Exceptions;

use ArsamMe\Wallet\Internal\Exceptions\InvalidArgumentExceptionInterface;
use InvalidArgumentException;

final class PriceNotSetException extends InvalidArgumentException implements InvalidArgumentExceptionInterface {}
