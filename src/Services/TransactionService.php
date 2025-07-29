<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DispatcherServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\RegulatorServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransactionServiceInterface;
use AliRaghebi\Wallet\Data\TransactionData;
use AliRaghebi\Wallet\Data\TransactionExtra;
use AliRaghebi\Wallet\Events\TransactionCreatedEvent;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Repositories\TransactionRepository;
use AliRaghebi\Wallet\WalletConfig;

readonly class TransactionService implements TransactionServiceInterface {
    public function __construct(
        private TransactionRepository $transactionRepository,
        private RegulatorServiceInterface $regulatorService,
        private ConsistencyServiceInterface $consistencyService,
        private CastServiceInterface $castService,
        private DispatcherServiceInterface $dispatcherService,
        private ClockServiceInterface $clockService,
        private IdentifierFactoryServiceInterface $identifierFactoryService,
        private WalletConfig $config,
    ) {}

    public function createTransaction(Wallet $wallet, string $type, string $amount, ?TransactionExtra $extra = null): Transaction {
        $wallet = $this->castService->getWallet($wallet);

        assert(in_array($type, [Transaction::TYPE_WITHDRAW, Transaction::TYPE_DEPOSIT]));
        $this->consistencyService->checkPositive($amount);

        if ($type == Transaction::TYPE_WITHDRAW) {
            $this->consistencyService->checkPotential($wallet, $amount);
            $amount = number($amount)->negated();
        }

        $uuid = $extra?->uuid ?? $this->identifierFactoryService->generate();
        $createdAt = $extra?->createdAt ?? $this->clockService->now();
        $updatedAt = $extra?->updatedAt ?? $this->clockService->now();

        $balance = $this->regulatorService->increase($wallet, $amount);

        $checksum = $this->consistencyService->createTransactionChecksum($uuid, $wallet->uuid, $type, $amount, $balance, $createdAt);

        $transaction = new TransactionData($uuid, $wallet->id, $type, $amount, $balance, $extra?->purpose, $extra?->description, $extra?->meta, $checksum, $createdAt, $updatedAt);
        $transaction = $this->transactionRepository->create($transaction);

        $this->dispatcherService->dispatch(TransactionCreatedEvent::fromTransaction($transaction));

        return $transaction;
    }

    public function deposit(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction {
        return $this->createTransaction($wallet, Transaction::TYPE_DEPOSIT, $amount, $extra);
    }

    public function withdraw(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction {
        return $this->createTransaction($wallet, Transaction::TYPE_WITHDRAW, $amount, $extra);
    }
}
