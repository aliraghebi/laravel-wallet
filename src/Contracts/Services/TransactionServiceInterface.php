<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Data\TransactionData;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Support\Collection;

interface TransactionServiceInterface {
    public function makeTransaction(Wallet $wallet, string $type, string $amount, ?array $meta = null): TransactionData;

    public function deposit(Wallet $wallet, string $amount, ?array $meta = null);

    public function withdraw(Wallet $wallet, string $amount, ?array $meta = null);

    public function apply(array $wallets, array $objects): array;
}
