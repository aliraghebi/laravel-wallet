<?php

namespace ArsamMe\Wallet\Transformers;

use ArsamMe\Wallet\Contracts\Transformers\WalletDataTransformerInterface;
use ArsamMe\Wallet\Data\WalletData;

class WalletDataTransformer implements WalletDataTransformerInterface {
    public function extract(WalletData $data): array {
        return [
            'uuid' => $data->uuid,
            'holder_type' => $data->holderType,
            'holder_id' => $data->holderId,
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'decimal_places' => $data->decimalPlaces,
            'meta' => $data->meta,
            'checksum' => $data->checksum,
            'created_at' => $data->createdAt,
            'updated_at' => $data->updatedAt,
        ];
    }
}
