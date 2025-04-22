<?php

namespace ArsamMe\Wallet\Contracts\Services;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Exceptions\InvalidAmountException;

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
     * @throws InvalidAmountException If the given amount is not positive.
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
     * @throws InvalidAmountException If the given balance or amount is not positive.
     */
    public function canWithdraw(float|int|string $balance, float|int|string $amount, bool $allowZero = false): bool;

    public function createWalletChecksum(string $uuid, string $balance, string $frozenAmount, int $transactionsCount, string $transactionsSum): ?string;

    public function createTransactionChecksum(string $uuid, string $walletId, string $type, string $amount, string $createdAt): ?string;

    public function createTransferChecksum(string $uuid, string $fromWalletId, string $toWalletId, string $amount, string $fee, string $createdAt): ?string;

    public function checkWalletConsistency(Wallet $wallet, ?string $checksum = null, bool $throw = false): bool;

    /**
     * @param  array<string, string>  $checksums  Array of checksums to check. Key may be `id` or `uuid`.
     * @param  string  $column  DB column name of `checksums` array keys.
     */
    public function checkMultiWalletConsistency(array $checksums, string $column = 'id'): void;
}
