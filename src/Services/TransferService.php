<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Contracts\Repositories\TransferRepositoryInterface;
use ArsamMe\Wallet\Contracts\Services\CastServiceInterface;
use ArsamMe\Wallet\Contracts\Services\ConsistencyServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\MathServiceInterface;
use ArsamMe\Wallet\Contracts\Services\TransactionServiceInterface;
use ArsamMe\Wallet\Contracts\Services\TransferServiceInterface;
use ArsamMe\Wallet\Contracts\Wallet;
use ArsamMe\Wallet\Data\TransferData;
use ArsamMe\Wallet\Data\TransferLazyData;
use ArsamMe\Wallet\Exceptions\InvalidAmountException;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use Str;

readonly class TransferService implements TransferServiceInterface {
    public function __construct(
        private TransactionServiceInterface $transactionService,
        private ConsistencyServiceInterface $consistencyService,
        private DatabaseServiceInterface $databaseService,
        private MathServiceInterface $mathService,
        private CastServiceInterface $castService,
        private TransferRepositoryInterface $transferRepository
    ) {}

    public function makeTransfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?array $meta = null): TransferLazyData {
        $this->consistencyService->checkPositive($amount);
        $this->consistencyService->checkPositive($fee);

        $from = $this->castService->getWallet($from);
        $to = $this->castService->getWallet($to);

        assert($from->getKey() != $to->getKey());

        $decimalPlaces = min($from->decimal_places, $to->decimal_places);
        $amount = $this->mathService->round($amount, $decimalPlaces);
        if ($this->mathService->compare($amount, 0) === 0) {
            throw new InvalidAmountException('This amount can not be transferred because of low decimal places on source or dest wallets.');
        }

        $withdrawalAmount = $this->mathService->intValue($amount, $from->decimal_places);
        $depositAmount = $this->mathService->intValue($this->mathService->sub($amount, $fee), $to->decimal_places);

        $amount = $this->mathService->intValue($amount, $decimalPlaces);
        $fee = $this->mathService->intValue($fee, $decimalPlaces);

        $withdrawal = $this->transactionService->makeTransaction($from, Transaction::TYPE_WITHDRAW, $withdrawalAmount);
        $deposit = $this->transactionService->makeTransaction($to, Transaction::TYPE_DEPOSIT, $depositAmount);

        return new TransferLazyData(
            Str::uuid7()->toString(),
            $from,
            $to,
            $amount,
            $fee,
            $decimalPlaces,
            $withdrawal,
            $deposit,
            $meta
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function transfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?array $meta = null): Transfer {
        $transfer = $this->makeTransfer($from, $to, $amount, $fee, $meta);

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

                $operations[] = $object->withdrawData;
                $operations[] = $object->depositData;
            }

            $transactions = $this->transactionService->apply($wallets, $operations);

            $links = [];
            $transfers = [];
            foreach ($objects as $object) {
                $withdraw = $transactions[$object->withdrawData->uuid] ?? null;
                assert($withdraw instanceof Transaction);

                $deposit = $transactions[$object->depositData->uuid] ?? null;
                assert($deposit instanceof Transaction);

                $fromWallet = $this->castService->getWallet($object->fromWallet);
                $toWallet = $this->castService->getWallet($object->toWallet);

                $now = now()->toImmutable();
                $checksum = $this->consistencyService->createTransferChecksum($object->uuid, $fromWallet->getKey(), $toWallet->getKey(), $object->amount, $object->fee, $now);

                $transfer = new TransferData(
                    $object->uuid,
                    $withdraw->getKey(),
                    $deposit->getKey(),
                    $fromWallet->getKey(),
                    $toWallet->getKey(),
                    $object->amount,
                    $object->fee,
                    $object->decimalPlaces,
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
