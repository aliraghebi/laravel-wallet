<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Exceptions\ModelNotFoundException;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface WalletServiceInterface {
    public function createWallet(
        Model $holder,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?array $meta = null,
        ?string $uuid = null
    ): WalletModel;

    /**
     * Find a wallet by its ID.
     *
     * @param  int  $id  The ID of the wallet to find.
     * @return WalletModel|null The wallet with the given ID if found, otherwise null.
     */
    public function findById(int $id): ?WalletModel;

    /**
     * Find a wallet by its UUID.
     *
     * @param  string  $uuid  The UUID of the wallet to find.
     * @return WalletModel|null The wallet with the given UUID if found, otherwise null.
     */
    public function findByUuid(string $uuid): ?WalletModel;

    /**
     * Find a wallet by its holder type, holder ID, and slug.
     *
     * @param  string  $slug  The wallet's slug.
     * @return WalletModel|null The wallet with the given holder type, holder ID, and slug if found, otherwise null.
     */
    public function findBySlug(Model $holder, string $slug): ?WalletModel;

    /**
     * Find a wallet by its ID.
     *
     * @param  int  $id  The ID of the wallet to find.
     * @return WalletModel The wallet with the given ID if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailById(int $id): WalletModel;

    /**
     * Find a wallet by its UUID.
     *
     * @param  string  $uuid  The UUID of the wallet to find.
     * @return WalletModel The wallet with the given UUID if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailByUuid(string $uuid): WalletModel;

    /**
     * Find a wallet by its holder type, holder ID, and slug.
     *
     * @param  string  $slug  The wallet's slug.
     * @return WalletModel The wallet with the given holder type, holder ID, and slug if found, otherwise null.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFailBySlug(Model $holder, string $slug): WalletModel;

    public function getBalance(Wallet $wallet): string;

    public function deposit(Wallet $wallet, int|float|string $amount, ?TransactionExtra $extra = null): Transaction;

    public function withdraw(Wallet $wallet, int|float|string $amount, ?TransactionExtra $extra = null): Transaction;

    public function transfer(Wallet $from, Wallet $to, int|float|string $amount, int|float|string $fee = 0, ?TransferExtra $extra = null): Transfer;

    public function freeze(Wallet $wallet, int|float|string|null $amount = null, bool $allowOverdraft = false): bool;

    public function unFreeze(Wallet $wallet, int|float|string|null $amount = null): bool;

    public function atomic(Collection|Wallet|array $wallets, $callback): mixed;
}
