<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Data\TransactionData;
use ArsamMe\Wallet\Data\TransactionExtra;
use ArsamMe\Wallet\Models\Transaction;

interface TransactionServiceInterface {
    public function makeTransaction(Wallet $wallet, string $type, string $amount, ?TransactionExtra $extra = null): TransactionData;

    public function deposit(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction;

    public function withdraw(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction;

    public function apply(array $wallets, array $objects): array;
}
