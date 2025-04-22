<?php

namespace ArsamMe\Wallet\Test\Infra\Exceptions;

use ArsamMe\Wallet\Internal\Exceptions\RuntimeExceptionInterface;
use RuntimeException;

final class UnknownEventException extends RuntimeException implements RuntimeExceptionInterface {}
