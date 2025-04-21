<?php

namespace ArsamMe\Wallet\Repositories;

use ArsamMe\Wallet\Contracts\Repositories\TransactionRepositoryInterface;
use ArsamMe\Wallet\Contracts\Transformers\TransactionDataTransformerInterface;
use ArsamMe\Wallet\Data\TransactionData;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Utils\JsonUtil;
use Illuminate\Support\Collection;

readonly class TransactionRepository implements TransactionRepositoryInterface {
    public function __construct(
        private Transaction $transaction,
        private TransactionDataTransformerInterface $transactionDataTransformer,
    ) {}

    public function create(TransactionData $data): Transaction {
        $attributes = $this->transactionDataTransformer->extract($data);
        $instance = $this->transaction->newInstance($attributes);
        $instance->saveQuietly();

        return $instance;
    }

    public function insertMultiple(array $transactions): void {
        $values = [];
        foreach ($transactions as $transaction) {
            $values[] = array_map(
                fn ($value) => is_array($value) ? JsonUtil::encode($value) : $value,
                $this->transactionDataTransformer->extract($transaction)
            );
        }

        $this->transaction->newQuery()->insert($values);
    }

    public function multiGet(array $keys, string $column = 'id'): Collection {
        return $this->transaction->whereIn($column, $keys)->get();
    }
}
