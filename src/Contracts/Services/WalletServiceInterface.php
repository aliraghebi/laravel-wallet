<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletServiceInterface {
    public function createWallet(Model $holder, string $name, ?string $slug = null, ?int $decimalPlaces = null, ?array $meta = null, array $params = []): Wallet;

    public function findWalletBySlug(Model $holder, string $slug): ?Wallet;

    public function findOrFailWalletBySlug(Model $holder, string $slug): Wallet;

    public function getBalance(Wallet $wallet): string;

    public function deposit(Wallet $wallet, int|float|string $amount, ?array $meta = null): void;

    public function withdraw(Wallet $wallet, int|float|string $amount, ?array $meta = null): void;

    public function freeze(Wallet $wallet, int|float|string $amount, ?array $meta = null): void;

    public function unFreeze(Wallet $wallet, null|int|float|string $amount = null, ?array $meta = null): void;

    public function atomic(Wallet|array $wallets, $callback): mixed;

    public function validateWalletIntegrity(Wallet $wallet, bool $throw = false): bool;
}
