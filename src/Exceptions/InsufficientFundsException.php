<?php

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use LogicException;

final class InsufficientFundsException extends LogicException implements ExceptionInterface {}
