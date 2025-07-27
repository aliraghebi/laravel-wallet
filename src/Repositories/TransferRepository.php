<?php

namespace AliRaghebi\Wallet\Repositories;

use AliRaghebi\Wallet\Contracts\Repositories\TransferRepositoryInterface;
use AliRaghebi\Wallet\Data\TransferData;
use AliRaghebi\Wallet\Models\Transfer;

readonly class TransferRepository implements TransferRepositoryInterface {
    public function __construct(private Transfer $transfer) {}

    public function create(TransferData $data): Transfer {
        $instance = $this->transfer->newInstance($data->toArray());
        $instance->saveQuietly();

        return $instance;
    }
}
