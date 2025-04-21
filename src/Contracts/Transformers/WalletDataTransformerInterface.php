<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Contracts\Transformers;

use ArsamMe\Wallet\Data\WalletData;

interface WalletDataTransformerInterface {
    public function extract(WalletData $data): array;
}
