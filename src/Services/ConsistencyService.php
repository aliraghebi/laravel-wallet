<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Exceptions\BalanceIsEmptyException;
use AliRaghebi\Wallet\Exceptions\InsufficientFundsException;
use AliRaghebi\Wallet\Exceptions\InvalidAmountException;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;
use AliRaghebi\Wallet\WalletConfig;
use DateTimeInterface;

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
        if (number($amount)->isLessThan(0)) {
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

        if (number($amount)->isGreaterThan(0) && number($balance)->isLessOrEqual(0)) {
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
        return number($balance)->isGreaterOrEqual($amount);
    }

    public function createWalletChecksum(string $uuid, string $balance, string $frozenAmount, DateTimeInterface $updatedAt): ?string {
        $data = [
            $uuid,
            number($balance)->toString(),
            number($frozenAmount)->toString(),
            $updatedAt->getTimestamp(),
        ];

        return $this->createChecksum($data);
    }

    public function createTransactionChecksum(string $uuid, string $walletId, string $type, string $amount, string $balance, DateTimeInterface $createdAt): ?string {
        $data = [
            $uuid,
            $walletId,
            $type,
            number($amount)->toString(),
            number($balance)->toString(),
            $createdAt->getTimestamp(),
        ];

        return $this->createChecksum($data);
    }

    public function createTransferChecksum(string $uuid, string $fromWalletId, string $toWalletId, string $amount, string $fee, DateTimeInterface $createdAt): ?string {
        $data = [
            $uuid,
            $fromWalletId,
            $toWalletId,
            number($amount)->toString(),
            number($fee)->toString(),
            $createdAt->getTimestamp(),
        ];

        return $this->createChecksum($data);
    }

    public function validateWalletChecksum(Wallet $wallet, ?string $checksum = null): bool {
        $wallet = $this->castService->getWallet($wallet, false);

        $expectedChecksum = $this->createWalletChecksum($wallet->uuid, $wallet->getRawOriginal('balance', '0'), $wallet->getRawOriginal('frozen_amount', '0'), $wallet->updated_at);
        $checksum ??= $wallet->checksum;

        return $checksum == $expectedChecksum;
    }

    public function validateTransactionChecksum(Transaction $transaction, ?string $checksum = null): bool {
        $expectedChecksum = $this->createTransactionChecksum(
            $transaction->uuid,
            $transaction->wallet_id,
            $transaction->type,
            $transaction->getRawOriginal('amount'),
            $transaction->getRawOriginal('balance'),
            $transaction->created_at
        );
        $checksum ??= $transaction->checksum;

        return $checksum == $expectedChecksum;
    }

    public function validateTransferChecksum(Transfer $transfer, ?string $checksum = null): bool {
        $expectedChecksum = $this->createTransferChecksum(
            $transfer->uuid,
            $transfer->from_id,
            $transfer->to_id,
            $transfer->getRawOriginal('amount'),
            $transfer->getRawOriginal('fee'),
            $transfer->created_at
        );
        $checksum ??= $transfer->checksum;

        return $checksum == $expectedChecksum;
    }

    private function createChecksum(array $data): ?string {
        if (!$this->config->integrity_validation_enabled) {
            return null;
        }

        $stringToSign = implode('_', $data);
        $secret = $this->config->integrity_validation_secret;

        return hash_hmac('sha256', $stringToSign, $secret);
    }
}
