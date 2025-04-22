<?php

namespace ArsamMe\Wallet\Test\Infra\PackageModels;

final class MyWallet extends \ArsamMe\Wallet\Models\Wallet {
    public function helloWorld(): string {
        return 'hello world';
    }
}
