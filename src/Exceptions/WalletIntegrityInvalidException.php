<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use LogicException;

final class WalletIntegrityInvalidException extends LogicException implements ExceptionInterface {}
