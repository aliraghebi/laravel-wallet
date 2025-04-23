<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Exceptions\BalanceIsEmpty;
use ArsamMe\Wallet\Exceptions\InsufficientFunds;
use ArsamMe\Wallet\Exceptions\InvalidAmountException;
use ArsamMe\Wallet\Exceptions\WalletConsistencyException;
use DateTimeImmutable;

/**
 * @internal
 */
final readonly class ConsistencyService implements ConsistencyServiceInterface {
    public function __construct(
        private MathServiceInterface $mathService,
        private CastServiceInterface $castService,
        private WalletRepositoryInterface $walletRepository,
        private bool $consistencyChecksumsEnabled,
        private string $checksumSecret,
    ) {}

    /**
     * @throws InvalidAmountException
     */
    public function checkPositive(float|int|string $amount): void {
        if ($this->mathService->compare($amount, 0) === -1) {
            throw new InvalidAmountException(
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
        $balance = $wallet->getRawBalance();
        $availableBalance = $wallet->getRawAvailableBalance();

        if (($this->mathService->compare($amount, 0) !== 0) && ($this->mathService->compare($balance, 0) === 0)) {
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

    public function createWalletChecksum(string $uuid, string $balance, string $frozenAmount, int $transactionsCount, string $transactionsSum): ?string {
        if (!$this->consistencyChecksumsEnabled) {
            return null;
        }

        $dataToSign = [
            $uuid,
            $this->mathService->round($balance),
            $this->mathService->round($frozenAmount),
            $transactionsCount,
            $this->mathService->round($transactionsSum),
        ];

        $stringToSign = implode('_', $dataToSign);

        return hash_hmac('sha256', $stringToSign, $this->checksumSecret);
    }

    public function createTransactionChecksum(string $uuid, string $walletId, string $type, string $amount, DateTimeImmutable $createdAt): ?string {
        if (!$this->consistencyChecksumsEnabled) {
            return null;
        }

        $dataToSign = [
            $uuid,
            $walletId,
            $type,
            $amount,
            $createdAt->getTimestamp(),
        ];

        $stringToSign = implode('_', $dataToSign);

        return hash_hmac('sha256', $stringToSign, $this->checksumSecret);
    }

    public function createTransferChecksum(string $uuid, string $fromWalletId, string $toWalletId, string $amount, string $fee, DateTimeImmutable $createdAt): ?string {
        if (!$this->consistencyChecksumsEnabled) {
            return null;
        }

        $dataToSign = [
            $uuid,
            $fromWalletId,
            $toWalletId,
            $amount,
            $fee,
            $createdAt->getTimestamp(),
        ];

        $stringToSign = implode('_', $dataToSign);

        return hash_hmac('sha256', $stringToSign, $this->checksumSecret);
    }

    public function checkWalletConsistency(Wallet $wallet, ?string $checksum = null, bool $throw = false): bool {
        try {
            $wallet = $this->castService->getWallet($wallet, false);
            $this->checkMultiWalletConsistency([$wallet->id => $checksum ?? $wallet->checksum]);
        } catch (WalletConsistencyException $e) {
            if ($throw) {
                throw $e;
            }

            return false;
        }

        return true;

    }

    public function checkMultiWalletConsistency(array $checksums, string $column = 'id'): void {
        if (!$this->consistencyChecksumsEnabled) {
            return;
        }

        $wallets = $this->walletRepository->multiGet(array_keys($checksums), $column);
        foreach ($wallets as $wallet) {
            $expectedChecksum = $this->createWalletChecksum(
                $wallet->uuid,
                (string) $wallet->getRawOriginal('balance'),
                (string) $wallet->getRawOriginal('frozen_amount'),
                $wallet->transactions_count,
                (string) $wallet->transactions_sum,
            );

            $checksum = $checksums[$wallet[$column]];
            if ($checksum !== $expectedChecksum) {
                throw new WalletConsistencyException(
                    'Wallet consistency could not be verified.',
                    ExceptionInterface::WALLET_INCONSISTENCY
                );
            }
        }
    }
}
