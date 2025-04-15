<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Data\CreateTransactionData;
use ArsamMe\Wallet\Data\CreateWalletData;
use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletServiceInterface {
    public function createWallet(CreateWalletData $data): Wallet;

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

    public function deposit(Wallet $wallet, CreateTransactionData $data): void;

    public function withdraw(Wallet $wallet, CreateTransactionData $data): void;

    public function freeze(Wallet $wallet, int|float|string|null $amount = null): void;

    public function unFreeze(Wallet $wallet, int|float|string|null $amount = null): void;

    public function atomic(Wallet|array $wallets, $callback): mixed;

    public function checkWalletConsistency(Wallet $wallet, bool $throw = false): bool;
}
