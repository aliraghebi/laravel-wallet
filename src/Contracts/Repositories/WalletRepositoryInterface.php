<?php

namespace ArsamMe\Wallet\Contracts\Repositories;

use ArsamMe\Wallet\Data\WalletData;
use ArsamMe\Wallet\Data\WalletSumData;
use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Wallet;
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

    public function sumWallets(array $ids = [], array $uuids = [], array $slugs = []): WalletSumData;
}
