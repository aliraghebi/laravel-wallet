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

readonly class TransactionService implements TransactionServiceInterface {
    public function __construct(
        private TransactionRepository $transactionRepository,
        private RegulatorServiceInterface $regulatorService,
        private ConsistencyServiceInterface $consistencyService,
        private CastServiceInterface $castService,
        private DispatcherServiceInterface $dispatcherService,
        private ClockServiceInterface $clockService,
        private IdentifierFactoryServiceInterface $identifierFactoryService,
    ) {}

    public function makeTransaction(Wallet $wallet, string $type, string $amount, ?TransactionExtra $extra = null): TransactionData {
        assert(in_array($type, [Transaction::TYPE_WITHDRAW, Transaction::TYPE_DEPOSIT]));
        $this->consistencyService->checkPositive($amount);

        if ($type == Transaction::TYPE_WITHDRAW) {
            $this->consistencyService->checkPotential($wallet, $amount);
            $amount = number($amount)->negated();
        }

        $uuid = $extra?->uuid ?? $this->identifierFactoryService->generate();
        $time = $this->clockService->now();

        $checksum = $this->consistencyService->createTransactionChecksum($uuid, $wallet->id, $type, $amount, $time);

        return new TransactionData($uuid, $wallet->id, $type, $amount, $extra?->purpose, $extra?->description, $extra?->meta, $checksum, $time, $time);
    }

    public function deposit(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction {
        $transaction = $this->makeTransaction($wallet, Transaction::TYPE_DEPOSIT, $amount, $extra);
        $transactions = $this->apply([$wallet->id => $wallet], [$transaction]);

        return current($transactions);
    }

    public function withdraw(Wallet $wallet, string $amount, ?TransactionExtra $extra = null): Transaction {
        $transaction = $this->makeTransaction($wallet, Transaction::TYPE_WITHDRAW, $amount, $extra);
        $transactions = $this->apply([$wallet->id => $wallet], [$transaction]);

        return current($transactions);
    }

    public function apply(array $wallets, array $objects): array {
        $transactions = $this->insertMultiple($objects);
        $totals = $this->getSums($objects);
        $counts = collect($objects)->countBy(fn ($item) => $item->walletId);
        assert(count($transactions) === count($objects));

        foreach ($counts as $walletId => $count) {
            $wallet = $wallets[$walletId] ?? null;
            assert($wallet instanceof Wallet);

            $object = $this->castService->getWallet($wallet);
            assert($object->getKey() === $walletId);

            $this->regulatorService->increase($object, $totals[$walletId], $count);
        }

        foreach ($transactions as $transaction) {
            $this->dispatcherService->dispatch(TransactionCreatedEvent::fromTransaction($transaction));
        }

        return $transactions;
    }

    /**
     * @param  array<TransactionData>  $objects
     */
    private function insertMultiple(array $objects) {
        if (count($objects) === 1) {
            $items = [$this->transactionRepository->create(reset($objects))];
        } else {
            $this->transactionRepository->insertMultiple($objects);
            $uuids = $this->getUuids($objects);
            $items = $this->transactionRepository->multiGet($uuids, 'uuid');
        }

        assert($items !== []);

        $results = [];
        foreach ($items as $item) {
            $results[$item->uuid] = $item;
        }

        return $results;
    }

    /**
     * @param  array<TransactionData>  $objects
     */
    private function getUuids(array $objects): array {
        return array_map(static fn ($object): string => $object->uuid, $objects);
    }

    /**
     * @param  array<TransactionData>  $objects
     */
    private function getSums(array $objects): array {
        $amounts = [];
        foreach ($objects as $object) {
            $amounts[$object->walletId] = number($amounts[$object->walletId] ?? 0)->plus($object->amount)->toString();
        }

        return $amounts;
    }
}
