<?php

namespace ArsamMe\Wallet\Contracts\Repositories;

use ArsamMe\Wallet\Models\Transaction;
use Carbon\Carbon;

interface TransactionRepositoryInterface {
    /**
     * Create a new transaction.
     *
     * @param array{
     *     uuid: string,
     *     wallet_id: int,
     *     type: string,
     *     amount: string,
     *     balance: string,
     *     meta: array|null,
     *     checksum: string,
     *     created_at?: Carbon|null,
     *     updated_at?: Carbon|null,
     * } $attributes
     */
    public function createTransaction(array $attributes): Transaction;
}
