<?php

namespace AliRaghebi\Wallet\Services;

use AliRaghebi\Wallet\Contracts\Exceptions\ExceptionInterface;
use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Repositories\TransferRepositoryInterface;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DatabaseServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\DispatcherServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransactionServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\TransferServiceInterface;
use AliRaghebi\Wallet\Data\TransferData;
use AliRaghebi\Wallet\Data\TransferExtra;
use AliRaghebi\Wallet\Data\TransferLazyData;
use AliRaghebi\Wallet\Events\TransferCreatedEvent;
use AliRaghebi\Wallet\Models\Transaction;
use AliRaghebi\Wallet\Models\Transfer;

readonly class TransferService implements TransferServiceInterface {
    public function __construct(
        private TransactionServiceInterface $transactionService,
        private ConsistencyServiceInterface $consistencyService,
        private DatabaseServiceInterface $databaseService,
        private CastServiceInterface $castService,
        private TransferRepositoryInterface $transferRepository,
        private DispatcherServiceInterface $dispatcherService,
        private ClockServiceInterface $clockService,
        private IdentifierFactoryServiceInterface $identifierFactoryService
    ) {}

    public function makeTransfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?TransferExtra $extra = null): TransferLazyData {
        $this->consistencyService->checkPositive($amount);
        $this->consistencyService->checkPositive($fee);

        $from = $this->castService->getWallet($from);
        $to = $this->castService->getWallet($to);

        $withdrawalAmount = number($amount)->plus($fee)->toString();
        $depositAmount = $amount;

        $withdrawal = $this->transactionService->makeTransaction(
            $from,
            Transaction::TYPE_WITHDRAW,
            $withdrawalAmount,
            $extra?->withdrawalExtra,
        );

        $deposit = $this->transactionService->makeTransaction(
            $to,
            Transaction::TYPE_DEPOSIT,
            $depositAmount,
            $extra?->depositExtra,
        );

        return new TransferLazyData(
            $extra?->uuid ?? $this->identifierFactoryService->generate(),
            $from,
            $to,
            $amount,
            $fee,
            $withdrawal,
            $deposit,
            $extra?->purpose,
            $extra?->description,
            $extra?->meta
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function transfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?TransferExtra $extra = null): Transfer {
        $transfer = $this->makeTransfer($from, $to, $amount, $fee, $extra);

        $transfers = $this->apply([$transfer]);

        return current($transfers);
    }

    public function apply(array $objects): array {
        return $this->databaseService->transaction(function () use ($objects): array {
            $wallets = [];
            $operations = [];
            foreach ($objects as $object) {
                /** @var TransferLazyData $object */
                $fromWallet = $this->castService->getWallet($object->fromWallet);
                $wallets[$fromWallet->getKey()] = $fromWallet;

                $toWallet = $this->castService->getWallet($object->toWallet);
                $wallets[$toWallet->getKey()] = $toWallet;

                $operations[] = $object->withdrawalData;
                $operations[] = $object->depositData;
            }

            $transactions = $this->transactionService->apply($wallets, $operations);

            $links = [];
            $transfers = [];
            foreach ($objects as $object) {
                $withdraw = $transactions[$object->withdrawalData->uuid] ?? null;
                assert($withdraw instanceof Transaction);

                $deposit = $transactions[$object->depositData->uuid] ?? null;
                assert($deposit instanceof Transaction);

                $fromWallet = $this->castService->getWallet($object->fromWallet);
                $toWallet = $this->castService->getWallet($object->toWallet);

                $now = $this->clockService->now();
                $checksum = $this->consistencyService->createTransferChecksum($object->uuid, $fromWallet->getKey(), $toWallet->getKey(), $object->amount, $object->fee, $now);

                $transfer = new TransferData(
                    $object->uuid,
                    $deposit->getKey(),
                    $withdraw->getKey(),
                    $fromWallet->getKey(),
                    $toWallet->getKey(),
                    $object->amount,
                    $object->fee,
                    $object->purpose,
                    $object->description,
                    $object->meta,
                    $checksum,
                    $now,
                    $now
                );

                $transfers[] = $transfer;
                $links[$transfer->uuid] = [
                    'deposit' => $deposit,
                    'withdraw' => $withdraw,
                    'from' => $fromWallet->withoutRelations(),
                    'to' => $toWallet->withoutRelations(),
                ];
            }

            $models = $this->insertMultiple($transfers);
            foreach ($models as $model) {
                $model->setRelations($links[$model->uuid] ?? []);
                $this->dispatcherService->dispatch(TransferCreatedEvent::fromTransfer($model));
            }

            return $models;
        });
    }

    /**
     * @param  array<TransferData>  $objects
     */
    private function insertMultiple(array $objects) {
        if (count($objects) === 1) {
            $items = [$this->transferRepository->create(reset($objects))];
        } else {
            $this->transferRepository->insertMultiple($objects);
            $uuids = $this->getUuids($objects);
            $items = $this->transferRepository->multiGet($uuids, 'uuid');
        }

        assert($items !== []);

        $results = [];
        foreach ($items as $item) {
            $results[$item->uuid] = $item;
        }

        return $results;
    }

    /**
     * @param  array<TransferData>  $objects
     */
    private function getUuids(array $objects): array {
        return array_map(static fn ($object): string => $object->uuid, $objects);
    }
}
