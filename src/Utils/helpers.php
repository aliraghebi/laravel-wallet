<?php

use AliRaghebi\Wallet\Utils\Number;

function uuid7(): string {
    return Str::uuid7()->toString();
}

function number(string|float|int|null $number): Number {
    return Number::of($number ?? 0);
}
