<?php

namespace ArsamMe\Wallet\Contracts\Repositories;

use ArsamMe\Wallet\Data\TransactionData;
use ArsamMe\Wallet\Models\Transaction;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface {
    public function create(TransactionData $data): Transaction;

    public function insertMultiple(array $transactions): void;

    public function multiGet(array $keys, string $column = 'id'): Collection;
}
