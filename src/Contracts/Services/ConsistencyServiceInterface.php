<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Exceptions\InvalidAmountException;

interface ConsistencyServiceInterface {
    /**
     * Checks if the given amount is positive.
     *
     * This method throws an AmountInvalid exception if the given amount is not positive.
     *
     * @param  string  $amount  The amount to check.
     *
     * @throws InvalidAmountException If the given amount is not positive.
     */
    public function checkPositive(string $amount): void;

    public function checkPotential(Wallet $object, string $amount): void;

    /**
     * Checks if the given balance can be safely withdrawn by the specified amount.
     *
     * This method returns true if the balance can be withdrawn, and false otherwise.
     *
     * @param  string  $balance  The balance to check.
     * @param  string  $amount  The amount to withdraw.
     * @return bool Returns true if the balance can be withdrawn, false otherwise.
     *
     * @throws InvalidAmountException If the given balance or amount is not positive.
     */
    public function canWithdraw(string $balance, string $amount): bool;
}
