<?php

namespace AliRaghebi\Wallet\Test\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use RuntimeException;

final class UnknownEventException extends RuntimeException implements ExceptionInterface {}
