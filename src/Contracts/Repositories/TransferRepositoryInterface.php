<?php

namespace ArsamMe\Wallet\Contracts\Repositories;

use ArsamMe\Wallet\Data\TransferData;
use ArsamMe\Wallet\Models\Transfer;
use Illuminate\Support\Collection;

interface TransferRepositoryInterface {
    public function create(TransferData $data): Transfer;

    public function insertMultiple(array $transfers): void;

    public function multiGet(array $keys, string $column = 'id'): Collection;
}
