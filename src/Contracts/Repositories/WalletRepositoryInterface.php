<?php

namespace ArsamMe\Wallet\Contracts\Repositories;

use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface WalletRepositoryInterface {
    /**
     * Create a new wallet.
     *
     * @param array{
     *     uuid: string,
     *     holder_type: string,
     *     holder_id: string|int,
     *     name: string,
     *     slug?: string,
     *     description?: string,
     *     decimal_places?: int,
     *     meta: array|null,
     *     checksum: string,
     *     created_at?: Carbon|null,
     *     updated_at?: Carbon|null,
     * } $attributes
     */
    public function createWallet(array $attributes): Wallet;

    /**
     * Find a wallet by its ID.
     *
     * @param  int  $id  The ID of the wallet to find.
     * @return Wallet|null The wallet with the given ID if found, otherwise null.
     */
    public function findById(int $id): ?Wallet;

    /**
     * Find a wallet by its UUID.
     *
     * @param  string  $uuid  The UUID of the wallet to find.
     * @return Wallet|null The wallet with the given UUID if found, otherwise null.
     */
    public function findByUuid(string $uuid): ?Wallet;

    /**
     * Find a wallet by its holder type, holder ID, and slug.
     *
     * @param  string  $holderType  The type of the wallet's holder.
     * @param  int|string  $holderId  The ID of the wallet's holder.
     * @param  string  $slug  The wallet's slug.
     * @return Wallet|null The wallet with the given holder type, holder ID, and slug if found, otherwise null.
     */
    public function findBySlug(string $holderType, int|string $holderId, string $slug): ?Wallet;

    /**
     * Find a wallet by its ID.
     *
     * @param  int  $id  The ID of the wallet to find.
     * @return Wallet The wallet with the given ID if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailById(int $id): Wallet;

    /**
     * Find a wallet by its UUID.
     *
     * @param  string  $uuid  The UUID of the wallet to find.
     * @return Wallet The wallet with the given UUID if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailByUuid(string $uuid): Wallet;

    /**
     * Find a wallet by its holder type, holder ID, and slug.
     *
     * @param  string  $holderType  The type of the wallet's holder.
     * @param  int|string  $holderId  The ID of the wallet's holder.
     * @param  string  $slug  The wallet's slug.
     * @return Wallet The wallet with the given holder type, holder ID, and slug if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailBySlug(string $holderType, int|string $holderId, string $slug): Wallet;

    public function update(Wallet $wallet, array $attributes): Wallet;

    public function multiUpdate(array $data): bool;

    public function multiGet(array $ids, string $column = 'id'): Collection;
}
