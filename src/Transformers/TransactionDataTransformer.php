<?php

namespace ArsamMe\Wallet\Transformers;

use ArsamMe\Wallet\Contracts\Transformers\TransactionDataTransformerInterface;
use ArsamMe\Wallet\Data\TransactionData;

class TransactionDataTransformer implements TransactionDataTransformerInterface {
    public function extract(TransactionData $data): array {
        return [
            'uuid' => $data->uuid,
            'wallet_id' => $data->walletId,
            'type' => $data->type,
            'amount' => $data->amount,
            'meta' => $data->meta,
            'checksum' => $data->checksum,
            'created_at' => $data->createdAt,
            'updated_at' => $data->updatedAt,
        ];
    }
}
