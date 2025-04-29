<?php

namespace ArsamMe\Wallet\Test\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use RuntimeException;

final class UnknownEventException extends RuntimeException implements ExceptionInterface {}
