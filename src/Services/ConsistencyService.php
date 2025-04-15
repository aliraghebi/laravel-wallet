<?php

declare(strict_types=1);

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Wallet;
use ArsamMe\Wallet\Exceptions\AmountInvalid;
use ArsamMe\Wallet\Exceptions\BalanceIsEmpty;
use ArsamMe\Wallet\Exceptions\InsufficientFunds;
use ArsamMe\Wallet\Exceptions\WalletConsistencyException;
use Carbon\Carbon;

/**
 * @internal
 */
final readonly class ConsistencyService implements ConsistencyServiceInterface {
    public function __construct(
        private MathServiceInterface $mathService,
        private CastServiceInterface $castService,
        private WalletRepositoryInterface $walletRepository
    ) {}

    /**
     * @throws AmountInvalid
     */
    public function checkPositive(float|int|string $amount): void {
        if (-1 === $this->mathService->compare($amount, 0)) {
            throw new AmountInvalid(
                'Amount must be positive.',
                ExceptionInterface::AMOUNT_INVALID
            );
        }
    }

    /**
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     */
    public function checkPotential(Wallet $object, string $amount, bool $allowZero = false): void {
        $wallet = $this->castService->getWallet($object, false);
        $balance = $wallet->getRawBalanceAttribute();
        $availableBalance = $wallet->getRawAvailableBalanceAttribute();

        if ((0 !== $this->mathService->compare($amount, 0)) && (0 === $this->mathService->compare($balance, 0))) {
            throw new BalanceIsEmpty(
                'Balance is empty.',
                ExceptionInterface::BALANCE_IS_EMPTY
            );
        }

        if (!$this->canWithdraw($availableBalance, $amount, $allowZero)) {
            throw new InsufficientFunds(
                'Insufficient funds.',
                ExceptionInterface::INSUFFICIENT_FUNDS
            );
        }
    }

    public function canWithdraw(float|int|string $balance, float|int|string $amount, bool $allowZero = false): bool {
        $mathService = app(MathServiceInterface::class);

        /**
         * Allow buying for free with a negative balance.
         */
        if ($allowZero && !$mathService->compare($amount, 0)) {
            return true;
        }

        return $mathService->compare($balance, $amount) >= 0;
    }

    public function createWalletInitialChecksum(string $uuid, string $time): string {
        return $this->createWalletChecksum($uuid, '0', '0', 0, '0', $time);
    }

    public function createWalletChecksum(string $uuid, string $balance, string $frozenAmount, int $transactionsCount, string $transactionsSum, string $updatedAt): string {
        $dataToSign = [
            $uuid,
            $this->mathService->round($balance),
            $this->mathService->round($frozenAmount),
            $transactionsCount,
            $this->mathService->round($transactionsSum),
            $updatedAt,
        ];


        $stringToSign = implode('_', $dataToSign);
        $secret = config('wallet.consistency.secret');

        return hash_hmac('sha256', $stringToSign, $secret);
    }

    public function createTransactionChecksum(string $uuid, string $walletId, string $type, string $amount, string $createdAt): string {
        $dataToSign = [
            $uuid,
            $walletId,
            $type,
            $amount,
            $createdAt,
        ];

        $stringToSign = implode('_', $dataToSign);
        $secret = config('wallet.consistency.secret');

        return hash_hmac('sha256', $stringToSign, $secret);
    }

    public function checkWalletConsistency(Wallet $wallet, bool $throw = false): bool {
        $wallet = $this->castService->getWallet($wallet);

        $walletState = $this->walletRepository->getWalletStateData($wallet);
        $expectedChecksum = $this->createWalletChecksum(
            $walletState->uuid,
            $walletState->balance,
            $walletState->frozenAmount,
            $walletState->transactionsCount,
            $walletState->transactionsSum,
            $walletState->updatedAt
        );

        if ($wallet->checksum !== $expectedChecksum || (0 !== $this->mathService->compare($wallet->getRawBalanceAttribute(), $walletState->transactionsSum))) {
            if ($throw) {
                throw new WalletConsistencyException(
                    'Wallet consistency could not be verified.',
                    ExceptionInterface::WALLET_INCONSISTENCY
                );
            }

            return false;
        }

        return true;
    }
}
