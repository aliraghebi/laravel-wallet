<?php

namespace AliRaghebi\Wallet\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use LogicException;

final class BalanceIsEmptyException extends LogicException implements ExceptionInterface {}
