<?php

namespace ArsamMe\Wallet\Repositories;

use ArsamMe\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use ArsamMe\Wallet\Models\Transaction;

readonly class TransactionRepository implements TransactionRepositoryInterface {
    public function __construct(private Transaction $transaction) {}

    public function createTransaction(array $attributes): Transaction {
        $attributes['created_at'] ??= now();
        $attributes['updated_at'] ??= now();

        $instance = $this->transaction->newInstance($attributes);
        $instance->saveQuietly();

        return $instance;
    }
}
