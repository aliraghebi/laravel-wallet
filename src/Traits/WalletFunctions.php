<?php

namespace AliRaghebi\Wallet\Traits;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\AtomicServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\MathServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\RegulatorServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\WalletServiceInterface;
use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Exceptions\BalanceIsEmptyException;
use AliRaghebi\Wallet\Exceptions\InsufficientFundsException;
use AliRaghebi\Wallet\Exceptions\InvalidAmountException;
use AliRaghebi\Wallet\Exceptions\ModelNotFoundException;
use AliRaghebi\Wallet\Exceptions\TransactionFailedException;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\RecordsNotFoundException;

use function app;
use function config;

trait WalletFunctions {
    /**
     * Magic Laravel framework method that makes it possible to call property balance.
     *
     * This method is called by Laravel magic getter when the `balance` property is accessed.
     * It returns the current balance of the wallet as a string.
     *
     * @return non-empty-string The current balance of the wallet as a string.
     *
     * @throws ModelNotFoundException If the wallet does not exist and `$save` is set to `false`.
     *
     * @see Wallet
     * @see WalletModel
     */
    public function getRawBalance(): string {
        // Get the wallet object from the model.
        // This method uses the CastServiceInterface to retrieve the wallet object from the model.
        // The second argument, `$save = false`, prevents the service from saving the wallet if it does not exist.
        // This is useful to avoid unnecessary database queries when retrieving the balance.
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Get the current balance of the wallet using the Regulator service.
        // This method uses the RegulatorServiceInterface to retrieve the current balance of the wallet.
        // The Regulator service is responsible for calculating the balance of the wallet based on the transactions.
        // The balance is always returned as a string to preserve the accuracy of the decimal value.
        // Return the balance as a string.
        return app(RegulatorServiceInterface::class)->getBalance($wallet);
    }

    public function getRawFrozenAmount(): string {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        return app(RegulatorServiceInterface::class)->getFrozenAmount($wallet);
    }

    public function getRawAvailableBalance(): string {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        return app(RegulatorServiceInterface::class)->getAvailableBalance($wallet);
    }

    public function getBalanceAttribute(): string {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        return app(MathServiceInterface::class)->floatValue($this->getRawBalance(), $wallet->decimal_places);
    }

    public function getBalanceFloatAttribute(): float {
        return (float) $this->getBalanceAttribute();
    }

    public function getBalanceIntAttribute(): int {
        return (int) $this->getBalanceAttribute();
    }

    public function getFrozenAmountAttribute(): string {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        return app(MathServiceInterface::class)->floatValue($this->getRawFrozenAmount(), $wallet->decimal_places);
    }

    public function getFrozenAmountFloatAttribute(): float {
        return (float) $this->getFrozenAmountAttribute();
    }

    public function getFrozenAmountIntAttribute(): int {
        return (int) $this->getFrozenAmountAttribute();
    }

    public function getAvailableBalanceAttribute(): string {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        return app(MathServiceInterface::class)->floatValue($this->getRawAvailableBalance(), $wallet->decimal_places);
    }

    public function getAvailableBalanceFloatAttribute(): float {
        return (float) $this->getAvailableBalanceAttribute();
    }

    public function getAvailableBalanceIntAttribute(): int {
        return (int) $this->getAvailableBalanceAttribute();
    }

    /**
     * Deposit funds into the wallet.
     *
     * This method executes the deposit transaction within an atomic block to ensure data consistency.
     *
     * @param  float|int|string  $amount  The amount to deposit.
     * @return Transaction The transaction object representing the deposit.
     */
    public function deposit(float|int|string $amount, ?TransactionExtra $extra = null): Transaction {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Execute the deposit transaction within an atomic block to ensure data consistency.
        return app(WalletServiceInterface::class)->deposit($wallet, $amount, $extra);
    }

    /**
     * Withdraw funds from the system.
     *
     * This method wraps the withdrawal in an atomic block to ensure atomicity and consistency of the withdrawal.
     * It checks if the withdrawal is possible before attempting it.
     *
     * @param  float|int|string  $amount  The amount to withdraw.
     * @return Transaction The created transaction.
     *
     * @see AtomicServiceInterface
     * @see ConsistencyServiceInterface
     * @see TransactionFailedException
     * @see InvalidAmountException
     * @see BalanceIsEmptyException
     * @see InsufficientFundsException
     * @see RecordsNotFoundException
     */
    public function withdraw(float|int|string $amount, ?TransactionExtra $extra = null): Transaction {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Execute the deposit transaction within an atomic block to ensure data consistency.
        return app(WalletServiceInterface::class)->withdraw($wallet, $amount, $extra);
    }

    public function freeze(float|int|string|null $amount = null, bool $allowOverdraft = false): bool {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Execute the deposit transaction within an atomic block to ensure data consistency.
        return app(WalletServiceInterface::class)->freeze($wallet, $amount, $allowOverdraft);
    }

    public function unFreeze(float|int|string|null $amount = null): bool {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Execute the deposit transaction within an atomic block to ensure data consistency.
        return app(WalletServiceInterface::class)->unFreeze($wallet, $amount);
    }

    public function transfer(Wallet $destination, float|int|string $amount, float|int|string $fee = 0, ?TransferExtra $extra = null): Transfer {
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);
        $destination = app(CastServiceInterface::class)->getWallet($destination);

        // Execute the deposit transaction within an atomic block to ensure data consistency.
        return app(WalletServiceInterface::class)->transfer($wallet, $destination, $amount, $fee, $extra);
    }

    /**
     * Checks if the user can withdraw funds based on the provided amount.
     *
     * This method retrieves the math service instance and calculates the total balance of the wallet.
     * It then checks if the withdrawal is possible using the consistency service.
     *
     * @param  float|int|string  $amount  The amount to be withdrawn.
     * @return bool Returns true if the withdrawal is possible; otherwise, false.
     */
    public function canWithdraw(float|int|string $amount): bool {
        // Get the math service instance.
        $mathService = app(MathServiceInterface::class);

        // Get the wallet and calculate the total balance.
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);
        $amount = $mathService->intValue($amount, $wallet->decimal_places);

        $balance = $this->getRawBalance();

        // Check if the withdrawal is possible.
        return app(ConsistencyServiceInterface::class)->canWithdraw($balance, $amount);
    }
}
