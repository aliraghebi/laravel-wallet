<?php

namespace AliRaghebi\Wallet\Contracts\Repositories;

use AliRaghebi\Wallet\Data\TransactionData;
use AliRaghebi\Wallet\Models\Transaction;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface {
    public function create(TransactionData $data): Transaction;

    public function insertMultiple(array $transactions): void;

    public function multiGet(array $keys, string $column = 'id'): Collection;
}
