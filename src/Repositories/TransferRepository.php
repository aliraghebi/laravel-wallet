<?php

namespace ArsamMe\Wallet\Repositories;

use ArsamMe\Wallet\Contracts\Repositories\TransferRepositoryInterface;
use ArsamMe\Wallet\Data\TransferData;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Utils\JsonUtil;
use Illuminate\Support\Collection;

readonly class TransferRepository implements TransferRepositoryInterface {
    public function __construct(private Transfer $transfer) {}

    public function create(TransferData $data): Transfer {
        $instance = $this->transfer->newInstance($data->toArray());
        $instance->saveQuietly();

        return $instance;
    }

    public function insertMultiple(array $transfers): void {
        $values = [];
        foreach ($transfers as $transfer) {
            $values[] = array_map(
                fn ($value) => is_array($value) ? JsonUtil::encode($value) : $value,
                $transfer->toArray()
            );
        }

        $this->transfer->newQuery()->insert($values);
    }

    public function multiGet(array $keys, string $column = 'id'): Collection {
        return $this->transfer->whereIn($column, $keys)->get();
    }
}
