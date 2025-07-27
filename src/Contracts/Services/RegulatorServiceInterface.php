<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Data\WalletStateData;

/**
 * @api
 */
interface RegulatorServiceInterface {
    /**
     * Forget the stored value for the given wallet.
     *
     * This method removes the stored value associated with the provided wallet from the storage.
     *
     * @param  Wallet  $wallet  The wallet to forget.
     * @return bool True if the value was successfully forgotten, false otherwise.
     */
    public function forget(Wallet $wallet): bool;

    /**
     * Calculate the difference between the current balance and the given value.
     *
     * This method subtracts the given value from the current balance and returns the result.
     *
     * @param  Wallet  $wallet  The wallet to calculate the difference for.
     * @return non-empty-string The difference, formatted as a string with the same decimal places as the wallet.
     */
    public function getBalanceDiff(Wallet $wallet): string;

    /**
     * Calculate the difference between the current frozenAmount and the given value.
     *
     * This method subtracts the given value from the current frozenAmount and returns the result.
     *
     * @param  Wallet  $wallet  The wallet to calculate the difference for.
     * @return non-empty-string The difference, formatted as a string with the same decimal places as the wallet.
     */
    public function getFrozenAmountDiff(Wallet $wallet): string;

    public function get(Wallet $wallet): WalletStateData;

    /**
     * Get the current balance of the wallet.
     *
     * This method retrieves the current balance of the wallet from the storage.
     *
     * @param  Wallet  $wallet  The wallet to get the balance for.
     * @return non-empty-string The current balance, formatted as a string with the same decimal places as the wallet.
     */
    public function getBalance(Wallet $wallet): string;

    /**
     * Get the current frozenAmount of the wallet.
     *
     * This method retrieves the current frozenAmount of the wallet from the storage.
     *
     * @param  Wallet  $wallet  The wallet to get the frozenAmount for.
     * @return non-empty-string The current frozenAmount, formatted as a string with the same decimal places as the wallet.
     */
    public function getFrozenAmount(Wallet $wallet): string;

    /**
     * Get the current availableBalance of the wallet.
     *
     * This method retrieves the current availableBalance of the wallet from the storage.
     *
     * @param  Wallet  $wallet  The wallet to get the availableBalance for.
     * @return non-empty-string The current availableBalance, formatted as a string with the same decimal places as the wallet.
     */
    public function getAvailableBalance(Wallet $wallet): string;

    /**
     * Increase the stored value for the given wallet by the given amount.
     *
     * This method increases the stored value associated with the provided wallet by the given amount.
     *
     * @param  Wallet  $wallet  The wallet to increase.
     * @param  non-empty-string  $value  The amount to increase the stored value by.
     * @return non-empty-string The updated stored value, formatted as a string with the same decimal places as the wallet.
     */
    public function increase(Wallet $wallet, string $value): string;

    /**
     * Decrease the stored value for the given wallet by the given amount.
     *
     * This method decreases the stored value associated with the provided wallet by the given amount.
     *
     * @param  Wallet  $wallet  The wallet to decrease.
     * @param  string  $value  The amount to decrease the stored value by.
     * @return string The updated stored value, formatted as a string with the same decimal places as the wallet.
     */
    public function decrease(Wallet $wallet, string $value): string;

    public function freeze(Wallet $wallet, ?string $value = null): string;

    public function unFreeze(Wallet $wallet, ?string $value = null): string;

    public function committing(): void;

    public function committed(): void;

    /**
     * Purge the stored values for all wallets.
     *
     * This method removes all stored values from the storage.
     */
    public function purge(): void;

    public function persist(Wallet $wallet): void;
}
