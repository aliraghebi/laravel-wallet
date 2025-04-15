<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Contracts\Exceptions;

use Throwable;

interface ExceptionInterface extends Throwable {
    public const AMOUNT_INVALID = -1;

    public const BALANCE_IS_EMPTY = -2;

    public const INSUFFICIENT_FUNDS = -3;

    public const CART_EMPTY = -4;

    public const LOCK_PROVIDER_NOT_FOUND = -5;

    public const RECORD_NOT_FOUND = -6;

    public const TRANSACTION_FAILED = -7;

    public const WALLET_INCONSISTENCY = -8;

    public const MODEL_NOT_FOUND = -9;
}
