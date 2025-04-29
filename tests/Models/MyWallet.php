<?php

namespace ArsamMe\Wallet\Test\Models;

use ArsamMe\Wallet\Models\Wallet;

final class MyWallet extends Wallet {
    public function helloWorld(): string {
        return 'hello world';
    }
}
