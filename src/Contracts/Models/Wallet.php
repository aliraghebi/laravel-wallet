<?php

namespace ArsamMe\Wallet\Contracts\Models;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Exceptions\BalanceIsEmpty;
use ArsamMe\Wallet\Exceptions\InsufficientFunds;
use ArsamMe\Wallet\Exceptions\InvalidAmountException;
use ArsamMe\Wallet\Exceptions\TransactionFailedException;
use ArsamMe\Wallet\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\RecordsNotFoundException;

interface Wallet {
    /**
     * Deposit the specified amount of money into the wallet.
     *
     * @param  float|int|string  $amount  The amount to deposit.
     * @param  array|null  $meta  Additional information for the transaction.
     * @return Transaction The created transaction.
     *
     * @throws InvalidAmountException If the amount is invalid.
     * @throws RecordsNotFoundException If the wallet is not found.
     * @throws TransactionFailedException If the transaction fails.
     * @throws ExceptionInterface If an exception occurs.
     */
    public function deposit(float|int|string $amount, ?array $meta = null): Transaction;

    /**
     * Withdraw the specified amount of money from the wallet.
     *
     * @param  float|int|string  $amount  The amount to withdraw.
     * @param  array|null  $meta  Additional information for the transaction.
     * @return Transaction The created transaction.
     *
     * @throws InvalidAmountException If the amount is invalid.
     * @throws BalanceIsEmpty If the balance is empty.
     * @throws InsufficientFunds If the amount exceeds the balance.
     * @throws RecordsNotFoundException If the wallet is not found.
     * @throws TransactionFailedException If the transaction fails.
     * @throws ExceptionInterface If an exception occurs.
     */
    public function withdraw(float|int|string $amount, ?array $meta = null): Transaction;

    /**
     * Checks if the wallet can safely withdraw the specified amount.
     *
     * @param  float|int|string  $amount  The amount to withdraw.
     * @param  bool  $allowZero  Whether to allow withdrawing when the balance is zero.
     * @return bool Returns true if the wallet can withdraw the specified amount, false otherwise.
     */
    public function canWithdraw(float|int|string $amount, bool $allowZero = false): bool;

    /**
     * Returns the balance of the wallet as a string.
     *
     * The balance is the total amount of funds held by the wallet.
     *
     * @return non-empty-string The balance of the wallet.
     */
    public function getRawBalance(): string;

    /**
     * Returns the balance of the wallet as a string.
     *
     * The balance is the total amount of funds held by the wallet.
     *
     * @return non-empty-string The balance of the wallet.
     */
    public function getBalanceAttribute(): string;

    public function getRawFrozenAmount(): string;

    public function getFrozenAmountAttribute(): string;

    public function getRawAvailableBalance(): string;

    public function getAvailableBalanceAttribute(): string;

    /**
     * Represents a relationship where a wallet has many transactions.
     *
     * @return HasMany<Transaction> A collection of transactions associated with this wallet.
     */
    public function transactions(): HasMany;
}
