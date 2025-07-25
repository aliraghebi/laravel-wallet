<?php

namespace AliRaghebi\Wallet\Repositories;

use AliRaghebi\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Services\JsonServiceInterface;
use AliRaghebi\Wallet\Data\TransactionData;
use AliRaghebi\Wallet\Models\Transaction;
use Illuminate\Support\Collection;

readonly class TransactionRepository implements TransactionRepositoryInterface {
    public function __construct(
        private Transaction $transaction,
        private JsonServiceInterface $jsonService
    ) {}

    public function create(TransactionData $data): Transaction {
        $instance = $this->transaction->newInstance($data->toArray());
        $instance->saveQuietly();

        return $instance;
    }

    public function insertMultiple(array $transactions): void {
        $values = [];
        foreach ($transactions as $transaction) {
            $values[] = array_map(
                fn ($value) => is_array($value) ? $this->jsonService->encode($value) : $value,
                $transaction->toArray()
            );
        }

        $this->transaction->newQuery()->insert($values);
    }

    public function multiGet(array $keys, string $column = 'id'): Collection {
        return $this->transaction->whereIn($column, $keys)->get();
    }
}
