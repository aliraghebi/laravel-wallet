<?php

namespace ArsamMe\Wallet\Exceptions;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use RuntimeException;

final class ModelNotFoundException extends RuntimeException implements ExceptionInterface {}
