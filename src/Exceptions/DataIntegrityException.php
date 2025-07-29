<?php

namespace AliRaghebi\Wallet\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use LogicException;

final class DataIntegrityException extends LogicException implements ExceptionInterface {}
