<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Contracts\Transformers;

use ArsamMe\Wallet\Data\TransferData;

interface TransferDataTransformerInterface {
    public function extract(TransferData $data): array;
}
