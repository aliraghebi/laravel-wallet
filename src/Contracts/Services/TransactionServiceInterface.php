<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Data\TransactionData;
use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Models\Transaction;

interface TransactionServiceInterface {
    public function makeTransaction(Wallet $wallet, string $type, string $amount, ?TransactionExtra $extra = null): TransactionData;

    public function deposit(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction;

    public function withdraw(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction;

    public function apply(array $wallets, array $objects): array;
}
