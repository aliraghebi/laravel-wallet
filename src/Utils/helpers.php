<?php

use AliRaghebi\Wallet\Utils\Number;

function number(string|float|int|null $number): Number {
    return Number::of($number ?? 0);
}
