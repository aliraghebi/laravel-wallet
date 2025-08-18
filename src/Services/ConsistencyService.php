<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Exceptions\BalanceIsEmptyException;
use AliRaghebi\Wallet\Exceptions\InsufficientFundsException;
use AliRaghebi\Wallet\Exceptions\InvalidAmountException;
use AliRaghebi\Wallet\Utils\Number;
use AliRaghebi\Wallet\WalletConfig;

/**
 * @internal
 */
final readonly class ConsistencyService implements ConsistencyServiceInterface {
    public function __construct(
        private CastServiceInterface $castService,
        private WalletConfig $config,
    ) {}

    /**
     * @throws InvalidAmountException
     */
    public function checkPositive(string $amount): void {
        if (Number::of($amount)->isLessThan(0)) {
            throw new InvalidAmountException(
                'Amount must be positive.',
                ExceptionInterface::AMOUNT_INVALID
            );
        }
    }

    /**
     * @throws BalanceIsEmptyException
     * @throws InsufficientFundsException
     */
    public function checkPotential(Wallet $object, string $amount): void {
        $wallet = $this->castService->getWallet($object, false);
        $balance = $wallet->getBalanceAttribute();
        $availableBalance = $wallet->getAvailableBalanceAttribute();

        if (Number::of($amount)->isGreaterThan(0) && Number::of($balance)->isLessOrEqual(0)) {
            throw new BalanceIsEmptyException(
                'Balance is empty.',
                ExceptionInterface::BALANCE_IS_EMPTY
            );
        }

        if (!$this->canWithdraw($availableBalance, $amount)) {
            throw new InsufficientFundsException(
                'Insufficient funds.',
                ExceptionInterface::INSUFFICIENT_FUNDS
            );
        }
    }

    public function canWithdraw(string $balance, string $amount): bool {
        return Number::of($balance)->isGreaterOrEqual($amount);
    }
}
