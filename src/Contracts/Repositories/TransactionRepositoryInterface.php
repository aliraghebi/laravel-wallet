<?php

namespace AliRaghebi\Wallet\Contracts\Repositories;

use AliRaghebi\Wallet\Data\TransactionData;
use AliRaghebi\Wallet\Models\Transaction;

interface TransactionRepositoryInterface {
    public function create(TransactionData $data): Transaction;
}
