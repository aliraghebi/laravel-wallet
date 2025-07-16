<?php

namespace ArsamMe\Wallet\Traits;

use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Services\AtomicServiceInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Contracts\Services\WalletServiceInterface;
use ArsamMe\Wallet\Data\TransactionExtra;
use ArsamMe\Wallet\Data\TransferExtra;
use ArsamMe\Wallet\Exceptions\BalanceIsEmptyException;
use ArsamMe\Wallet\Exceptions\InsufficientFundsException;
use ArsamMe\Wallet\Exceptions\InvalidAmountException;
use ArsamMe\Wallet\Exceptions\ModelNotFoundException;
use ArsamMe\Wallet\Exceptions\TransactionFailedException;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet as WalletModel;
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
     * Retrieves all transfers related to the wallet.
     *
     * This method retrieves all transfers associated with the wallet.
     * It uses the `getWallet` method of the `CastServiceInterface` to retrieve the wallet instance.
     * The `false` parameter indicates that the wallet should not be saved if it does not exist.
     * The method then uses the `hasMany` method on the wallet instance to retrieve all transfers related to the wallet.
     * The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
     * The relationship is defined using the `from_id` foreign key.
     *
     * @return HasMany<Transfer> The `HasMany` relationship object representing all transfers related to the wallet.
     */
    public function transfers(): HasMany {
        // Retrieve the wallet instance associated with the current model.
        // The `getWallet` method of the `CastServiceInterface` is used to retrieve the wallet instance.
        // The `false` parameter indicates that the wallet should not be saved if it does not exist.
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Retrieve all transfers associated with the wallet.
        // The `hasMany` method is used on the wallet instance to retrieve all transfers related to the wallet.
        // The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
        // The relationship is defined using the `from_id` foreign key.
        return $wallet
            ->hasMany(
                // Retrieve the transfer model class from the configuration.
                // The default value is `Transfer::class`.
                config('wallet.transfer.model', Transfer::class),
                // Define the foreign key for the relationship.
                // The foreign key is `from_id`.
                'from_id'
            );
    }

    /**
     * Retrieves all the receiving transfers to this wallet.
     *
     * This method retrieves all receiving transfers associated with the wallet.
     * It uses the `getWallet` method of the `CastServiceInterface` to retrieve the wallet instance.
     * The `false` parameter indicates that the wallet should not be saved if it does not exist.
     * The method then uses the `hasMany` method on the wallet instance to retrieve all receiving transfers related to the wallet.
     * The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
     * The relationship is defined using the `to_id` foreign key.
     *
     * @return HasMany<Transfer> The `HasMany` relationship object representing all receiving transfers related to the wallet.
     */
    public function receivedTransfers(): HasMany {
        // Retrieve the wallet instance associated with the current model.
        // The `getWallet` method of the `CastServiceInterface` is used to retrieve the wallet instance.
        // The `false` parameter indicates that the wallet should not be saved if it does not exist.
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Retrieve all receiving transfers associated with the wallet.
        // The `hasMany` method is used on the wallet instance to retrieve all receiving transfers related to the wallet.
        // The transfer model class is retrieved from the configuration using `config('wallet.transfer.model', Transfer::class)`.
        // The relationship is defined using the `to_id` foreign key.
        return $wallet
            ->hasMany(
                // Retrieve the transfer model class from the configuration.
                // The default value is `Transfer::class`.
                config('wallet.transfer.model', Transfer::class),
                // Define the foreign key for the relationship.
                // The foreign key is `to_id`.
                'to_id'
            );
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

    /**
     * Returns all transactions related to the wallet.
     *
     * This method retrieves all transactions associated with the wallet.
     * It uses the `getWallet` method of the `CastServiceInterface` to retrieve the wallet instance.
     * The `false` parameter indicates that the wallet should not be saved if it does not exist.
     * The method then uses the `hasMany` method on the wallet instance to retrieve all transactions related to the wallet.
     * The transaction model class is retrieved from the configuration using `config('wallet.transaction.model', Transaction::class)`.
     * The relationship is defined using the `wallet_id` foreign key.
     *
     * @return HasMany<Transaction> Returns a `HasMany` relationship of transactions related to the wallet.
     */
    public function walletTransactions(): HasMany {
        // Retrieve the wallet instance using the `getWallet` method of the `CastServiceInterface`.
        // The `false` parameter indicates that the wallet should not be saved if it does not exist.
        $wallet = app(CastServiceInterface::class)->getWallet($this, false);

        // Retrieve all transactions related to the wallet using the `hasMany` method on the wallet instance.
        // The transaction model class is retrieved from the configuration using `config('wallet.transaction.model', Transaction::class)`.
        // The relationship is defined using the `wallet_id` foreign key.
        return $wallet->hasMany(config('wallet.transaction.model', Transaction::class), 'wallet_id');
    }
}
