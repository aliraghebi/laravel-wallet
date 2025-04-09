<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletServiceInterface
{
    function createWallet(Model $holder, string $name, ?string $slug = null, ?int $decimalPlaces = null, ?array $meta = null, array $params = []): Wallet;

    function findWalletBySlug(Model $holder, string $slug): ?Wallet;

    function findOrFailWalletBySlug(Model $holder, string $slug): Wallet;

    function getBalance(Wallet $wallet): string;

    function deposit(Wallet $wallet, int|float|string $amount, ?array $meta = null): void;

    function withdraw(Wallet $wallet, int|float|string $amount, ?array $meta = null): void;

    function freeze(Wallet $wallet, int|float|string $amount, ?array $meta = null): void;

    function unFreeze(Wallet $wallet, null|int|float|string $amount = null, ?array $meta = null): void;

    function atomic(Wallet|array $wallets, $callback): mixed;

    function validateWalletIntegrity(Wallet $wallet, bool $throw = false): bool;
}