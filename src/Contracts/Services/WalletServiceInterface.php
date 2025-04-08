<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletServiceInterface
{
    function createWallet(Model $holder, string $name, ?string $slug = null, ?int $decimalPlaces = null, ?array $meta = null, array $params = []): Wallet;

    function getWallet(Model $holder, string $slug): Wallet;

    function getBalance(Wallet $wallet);

    function deposit(Wallet $wallet, int|float|string $amount, ?array $meta = null);

    function withdraw(Wallet $wallet, int|float|string $amount, ?array $meta = null);

    function freeze(Wallet $wallet, int|float|string $amount, ?array $meta = null);

    function unFreeze(Wallet $wallet, null|int|float|string $amount = null, ?array $meta = null);

    function atomic(Wallet|array $wallets, $callback);
}