<?php

namespace ArsamMe\Wallet\Test\Infra\Values;

final readonly class Money {
    public function __construct(
        public string $amount,
        public string $currency,
    ) {}
}
