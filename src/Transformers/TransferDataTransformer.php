<?php

namespace ArsamMe\Wallet\Transformers;

use ArsamMe\Wallet\Contracts\Transformers\TransferDataTransformerInterface;
use ArsamMe\Wallet\Data\TransferData;

class TransferDataTransformer implements TransferDataTransformerInterface {
    public function extract(TransferData $data): array {
        return [
            'uuid' => $data->uuid,
            'from_id' => $data->fromId,
            'to_id' => $data->toId,
            'deposit_id' => $data->depositId,
            'withdrawal_id' => $data->withdrawalId,
            'amount' => $data->amount,
            'fee' => $data->fee,
            'decimal_places' => $data->decimalPlaces,
            'meta' => $data->meta,
            'checksum' => $data->checksum,
            'created_at' => $data->createdAt,
            'updated_at' => $data->updatedAt,
        ];
    }
}
