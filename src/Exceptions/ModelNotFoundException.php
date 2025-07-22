<?php

namespace AliRaghebi\Wallet\Exceptions;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use RuntimeException;

final class ModelNotFoundException extends RuntimeException implements ExceptionInterface {}
