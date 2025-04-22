<?php

namespace ArsamMe\Wallet\Contracts\Exceptions;

use Throwable;

interface ExceptionInterface extends Throwable {
    public const AMOUNT_INVALID = -1;

    public const BALANCE_IS_EMPTY = -2;

    public const INSUFFICIENT_FUNDS = -3;

    public const RECORD_NOT_FOUND = -4;

    public const TRANSACTION_FAILED = -5;

    public const WALLET_INCONSISTENCY = -6;

    public const MODEL_NOT_FOUND = -7;
}
