<?php

namespace ArsamMe\Wallet\Test\Infra\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use RuntimeException;

final class UnknownEventException extends RuntimeException implements ExceptionInterface {}
