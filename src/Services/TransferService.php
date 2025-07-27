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
use AliRaghebi\Wallet\Events\TransferCreatedEvent;
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

    /**
     * @throws ExceptionInterface
     */
    public function transfer(Wallet $from, Wallet $to, string|float|int $amount, string|float|int $fee = 0, ?TransferExtra $extra = null): Transfer {
        $this->consistencyService->checkPositive($amount);
        $this->consistencyService->checkPositive($fee);

        $from = $this->castService->getWallet($from);
        $to = $this->castService->getWallet($to);

        $withdrawalAmount = number($amount)->plus($fee)->toString();
        $depositAmount = $amount;

        return $this->databaseService->transaction(function () use ($fee, $amount, $depositAmount, $to, $extra, $from, $withdrawalAmount): Transfer {
            $withdrawal = $this->transactionService->withdraw($from, $withdrawalAmount, $extra?->withdrawalExtra);
            $deposit = $this->transactionService->deposit($to, $depositAmount, $extra?->depositExtra);

            $uuid = $extra?->uuid ?? $this->identifierFactoryService->generate();

            $now = $this->clockService->now();
            $checksum = $this->consistencyService->createTransferChecksum($uuid, $from->getKey(), $to->getKey(), $amount, $fee, $now);

            $transfer = new TransferData(
                $uuid,
                $deposit->getKey(),
                $withdrawal->getKey(),
                $from->getKey(),
                $to->getKey(),
                $amount,
                $fee,
                $extra?->purpose,
                $extra?->description,
                $extra?->meta,
                $checksum,
                $now,
                $now
            );

            $model = $this->transferRepository->create($transfer);

            $model->setRelations([
                'deposit' => $deposit,
                'withdrawal' => $withdrawal,
                'from' => $from->withoutRelations(),
                'to' => $to->withoutRelations(),
            ]);

            $this->dispatcherService->dispatch(TransferCreatedEvent::fromTransfer($model));

            return $model;
        });
    }
}
