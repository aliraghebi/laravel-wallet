<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Models\Wallet;
use ArsamMe\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Exceptions\BalanceIsEmptyException;
use ArsamMe\Wallet\Exceptions\InsufficientFundsException;
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
     * @throws BalanceIsEmptyException
     * @throws InsufficientFundsException
     */
    public function checkPotential(Wallet $object, string $amount): void {
        $wallet = $this->castService->getWallet($object, false);
        $balance = $wallet->getRawBalance();
        $availableBalance = $wallet->getRawAvailableBalance();

        if (($this->mathService->compare($amount, 0) !== 0) && ($this->mathService->compare($balance, 0) === 0)) {
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

    public function canWithdraw(float|int|string $balance, float|int|string $amount): bool {
        $mathService = app(MathServiceInterface::class);

        return $mathService->compare($balance, $amount) >= 0;
    }

    public function createWalletChecksum(string $uuid, string|float|int $balance, string|float|int $frozenAmount, int $transactionsCount, string|float|int $transactionsSum): ?string {
        if (!$this->consistencyChecksumsEnabled) {
            return null;
        }

        $dataToSign = [
            $uuid,
            $this->mathService->stripTrailingZeros($balance),
            $this->mathService->stripTrailingZeros($frozenAmount),
            $transactionsCount,
            $this->mathService->stripTrailingZeros($transactionsSum),
        ];

        $stringToSign = implode('_', $dataToSign);

        return hash_hmac('sha256', $stringToSign, $this->checksumSecret);
    }

    public function createTransactionChecksum(string $uuid, string $walletId, string $type, string|float|int $amount, DateTimeImmutable $createdAt): ?string {
        if (!$this->consistencyChecksumsEnabled) {
            return null;
        }

        $dataToSign = [
            $uuid,
            $walletId,
            $type,
            $this->mathService->scale($amount),
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
                $wallet->getRawOriginal('balance', 0),
                $wallet->getRawOriginal('frozen_amount', 0),
                $wallet->transactions_count,
                $wallet->transactions_sum,
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
