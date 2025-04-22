<?php

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use LogicException;

final class WalletConsistencyException extends LogicException implements ExceptionInterface {}
