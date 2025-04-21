<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface WalletServiceInterface {
    public function createWallet(
        Model $holder,
        ?string $name = null,
        ?string $slug = null,
        ?int $decimalPlaces = null,
        ?string $description = null,
        ?array $meta = null,
        ?string $uuid = null
    ): Wallet;

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
     * @param  string  $slug  The wallet's slug.
     * @return Wallet|null The wallet with the given holder type, holder ID, and slug if found, otherwise null.
     */
    public function findBySlug(Model $holder, string $slug): ?Wallet;

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
     * @param  string  $slug  The wallet's slug.
     * @return Wallet The wallet with the given holder type, holder ID, and slug if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailBySlug(Model $holder, string $slug): Wallet;

    public function getBalance(Wallet $wallet): string;

    public function deposit(Wallet $wallet, int|float|string $amount, ?array $meta = null): Transaction;

    public function withdraw(Wallet $wallet, int|float|string $amount, ?array $meta = null): Transaction;

    public function transfer(Wallet $from, Wallet $to, int|float|string $amount, int|float|string $fee = 0, ?array $meta = null): Transfer;

    public function freeze(Wallet $wallet, int|float|string|null $amount = null): bool;

    public function unFreeze(Wallet $wallet, int|float|string|null $amount = null): bool;

    public function atomic(Collection|Wallet|array $wallets, $callback): mixed;

    public function checkWalletConsistency(Wallet $wallet, bool $throw = false): bool;
}
