<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Listeners;

use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\RegulatorServiceInterface;

final class TransactionBeginningListener {
    /**
     * This listener is responsible for purging all transactions and transfers
     * if it is the top level of a transaction.
     */
    public function __invoke(): void {
        // Get the current transaction level from the database connection
        $transactionLevel = app(DatabaseServiceInterface::class)->getConnection()->transactionLevel();

        // If the transaction level is 1, it means it is the top level of a transaction
        if (1 === $transactionLevel) {
            // Get the regulator service instance
            /** @var RegulatorServiceInterface $regulatorService */
            $regulatorService = app(RegulatorServiceInterface::class);

            // Purge all transactions and transfers
            // This method is called to ensure that all changes made to the database within the transaction
            // are reflected in the wallet's balance. It is important to note that this action is not reversible
            // and data loss is possible.
            $regulatorService->purge();
        }
    }
}
