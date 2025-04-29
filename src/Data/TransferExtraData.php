<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\Data\BaseData;

class TransferExtraData extends BaseData {
    public function __construct(
        public readonly ?string $uuid = null,
        public readonly ?array $meta = null,
        public readonly ?TransactionExtraData $deposit = null,
        public readonly ?TransactionExtraData $withdrawal = null,
    ) {}
}
