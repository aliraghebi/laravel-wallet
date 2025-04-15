<?php

namespace ArsamMe\Wallet\Data;

use ArsamMe\Wallet\Contracts\BaseData;
use Carbon\Carbon;

class WalletStateData extends BaseData {
    public function __construct(
        public string $uuid,
        public string $balance,
        public string $frozenAmount,
        public int $transactionsCount,
        public string $transactionsSum,
        public ?string $checksum,
        public string $updatedAt,
    ) {}

    public static function make(array $data = []): WalletStateData {
        return new self(
            $data['uuid'] ?? '',
            $data['balance'] ?? '0',
            $data['frozenAmount'] ?? '0',
            $data['transactionsCount'] ?? 0,
            $data['transactionsSum'] ?? '0',
            $data['checksum'] ?? '',
            $data['updatedAt'] ?? Carbon::now()->timestamp,
        );
    }
}
