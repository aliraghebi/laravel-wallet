<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Data\WalletStateData;
use AliRaghebi\Wallet\Exceptions\RecordNotFoundException;

interface BookkeeperServiceInterface {
    public function forget(Wallet $wallet): bool;

    public function getBalance(Wallet $wallet): string;

    public function getFrozenAmount(Wallet $wallet): string;

    public function getTransactionsCount(Wallet $wallet): int;

    public function sync(Wallet $wallet, WalletStateData $data): bool;

    public function get(Wallet $wallet): WalletStateData;

    public function multiGet(array $wallets): array;

    /**
     * Synchronizes multiple wallet balances with the proposed values.
     *
     * This comprehensive method takes an associative array with wallet UUIDs as keys and their new balance values as values.
     * It aims to align each wallet's current balance with its corresponding new value provided in the array. The process
     * determines whether to increase, decrease, or maintain the current balance based on a comparison between the current
     * and the given balance values.
     *
     * Operations:
     * - If the proposed balance is higher than the existing one, the wallet's balance is increased accordingly.
     * - If the proposed balance is lower, the wallet's balance is decreased to match the new value.
     * - If the proposed balance matches the current one, no changes are made to the wallet's balance.
     *
     * The method ensures that all specified wallets undergo the synchronization process, adhering to the given values,
     * and returns a boolean indicating the overall success of the operation.
     *
     * @param  non-empty-array<string, WalletStateData>  $items  An associative array mapping wallet UUIDs to their new balance values.
     *                                                           Each entry specifies the target balance for a wallet identified by its UUID.
     * @return bool True if all specified wallets were successfully synchronized with the given balances, false otherwise.
     *
     * @throws RecordNotFoundException Thrown if any of the specified wallets cannot be found in the system. This ensures
     *                                 that only existing wallets are processed, safeguarding against synchronization
     *                                 attempts on non-existent wallets.
     *
     * @see Wallet The Wallet entity that represents the individual wallets to be synchronized.
     * @see Wallet::uuid The property within the Wallet class that uniquely identifies each wallet.
     */
    public function multiSync(array $items): bool;
}
