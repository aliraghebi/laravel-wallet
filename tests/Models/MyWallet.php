<?php

namespace AliRaghebi\Wallet\Test\Models;

use AliRaghebi\Wallet\Models\Wallet;

final class MyWallet extends Wallet {
    public function helloWorld(): string {
        return 'hello world';
    }
}
