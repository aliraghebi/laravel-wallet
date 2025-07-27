<?php

namespace AliRaghebi\Wallet\Contracts\Repositories;

use AliRaghebi\Wallet\Data\TransferData;
use AliRaghebi\Wallet\Models\Transfer;
use Illuminate\Support\Collection;

interface TransferRepositoryInterface {
    public function create(TransferData $data): Transfer;
}
