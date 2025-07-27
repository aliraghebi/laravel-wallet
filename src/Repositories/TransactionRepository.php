<?php

namespace AliRaghebi\Wallet\Repositories;

use AliRaghebi\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use AliRaghebi\Wallet\Data\TransactionData;
use AliRaghebi\Wallet\Models\Transaction;

readonly class TransactionRepository implements TransactionRepositoryInterface {
    public function __construct(private Transaction $transaction) {}

    public function create(TransactionData $data): Transaction {
        $instance = $this->transaction->newInstance($data->toArray());
        $instance->saveQuietly();

        return $instance;
    }
}
