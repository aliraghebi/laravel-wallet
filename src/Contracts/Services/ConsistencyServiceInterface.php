<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Contracts\Wallet;
use ArsamMe\Wallet\Exceptions\AmountInvalid;
use Carbon\Carbon;

/**
 * @api
 */
interface ConsistencyServiceInterface {
    /**
     * Checks if the given amount is positive.
     *
     * This method throws an AmountInvalid exception if the given amount is not positive.
     *
     * @param  float|int|string  $amount  The amount to check.
     *
     * @throws AmountInvalid If the given amount is not positive.
     */
    public function checkPositive(float|int|string $amount): void;

    public function checkPotential(Wallet $object, string $amount, bool $allowZero = false): void;

    /**
     * Checks if the given balance can be safely withdrawn by the specified amount.
     *
     * This method returns true if the balance can be withdrawn, and false otherwise.
     *
     * @param  float|int|string  $balance  The balance to check.
     * @param  float|int|string  $amount  The amount to withdraw.
     * @param  bool  $allowZero  Whether to allow zero amounts. Defaults to false.
     * @return bool Returns true if the balance can be withdrawn, false otherwise.
     *
     * @throws AmountInvalid If the given balance or amount is not positive.
     */
    public function canWithdraw(float|int|string $balance, float|int|string $amount, bool $allowZero = false): bool;

    public function createWalletInitialChecksum(string $uuid, string $time): string;

    public function createWalletChecksum(string $uuid, string $balance, string $frozenAmount, int $transactionsCount, string $transactionsSum, string $updatedAt): string;

    public function createTransactionChecksum(string $uuid, string $walletId, string $type, string $amount, string $createdAt): string;

    public function checkWalletConsistency(Wallet $wallet, bool $throw = false): bool;
}
