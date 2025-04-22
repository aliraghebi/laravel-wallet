<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use LogicException;

final class BalanceIsEmpty extends LogicException implements ExceptionInterface {}
