<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Contracts\Transformers;

use ArsamMe\Wallet\Data\TransactionData;

interface TransactionDataTransformerInterface {
    public function extract(TransactionData $data): array;
}
