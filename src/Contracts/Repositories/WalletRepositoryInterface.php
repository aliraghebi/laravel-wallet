<?php

namespace AliRaghebi\Wallet\Contracts\Repositories;

use AliRaghebi\Wallet\Data\WalletData;
use AliRaghebi\Wallet\Data\WalletSumData;
use AliRaghebi\Wallet\Exceptions\ModelNotFoundException;
use AliRaghebi\Wallet\Models\Wallet;
use Illuminate\Support\Collection;

interface WalletRepositoryInterface {
    public function createWallet(WalletData $data): Wallet;

    /**
     * Find a wallet by given attributes.
     *
     * @return Wallet|null The wallet with the given ID if found, otherwise null.
     */
    public function findBy(array $attributes): ?Wallet;

    /**
     * Find a wallet by given attributes.
     *
     * @return Wallet The wallet with the given ID if found, otherwise throws ModelNotFoundException.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailBy(array $attributes): Wallet;

    public function update(Wallet $wallet, array $attributes): Wallet;

    public function multiUpdate(array $data): bool;

    public function multiGet(array $keys, string $column = 'id'): Collection;
}
