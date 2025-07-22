<?php

namespace AliRaghebi\Wallet\Contracts\Services;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Data\TransferLazyData;
use AliRaghebi\Wallet\Exceptions\RecordNotFoundException;
use AliRaghebi\Wallet\Exceptions\TransactionFailedException;
use AliRaghebi\Wallet\Models\Transfer;
use Illuminate\Database\RecordsNotFoundException;

interface TransferServiceInterface {
    public function makeTransfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?TransferExtra $extra = null): TransferLazyData;

    public function transfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?TransferExtra $extra = null): Transfer;

    /**
     * Applies a set of transfer operations in a single database transaction.
     *
     * This method takes an array of transfer objects and applies them,
     * creating transfers and corresponding transactions.
     *
     * @param  non-empty-array<TransferLazyData>  $objects  The array of transfer operations to apply.
     * @return non-empty-array<string, Transfer> An array of created transfers, indexed by their IDs.
     *
     * @throws RecordNotFoundException If a wallet referenced in the transfer operations is not found.
     * @throws RecordsNotFoundException If a wallet referenced in the transfer operations is not found.
     * @throws TransactionFailedException If the transaction fails for any reason.
     * @throws ExceptionInterface If an unexpected error occurs.
     */
    public function apply(array $objects): array;
}
