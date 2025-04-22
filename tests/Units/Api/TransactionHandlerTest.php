<?php

namespace ArsamMe\Wallet\Test\Units\Api;

use ArsamMe\Wallet\External\Api\TransactionFloatQuery;
use ArsamMe\Wallet\External\Api\TransactionQuery;
use ArsamMe\Wallet\External\Api\TransactionQueryHandlerInterface;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\PackageModels\Transaction;
use ArsamMe\Wallet\Test\Infra\TestCase;

use function app;

/**
 * @internal
 */
final class TransactionHandlerTest extends TestCase {
    public function test_wallet_not_exists(): void {
        /** @var TransactionQueryHandlerInterface $transactionHandler */
        $transactionHandler = app(TransactionQueryHandlerInterface::class);

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        self::assertFalse($buyer->relationLoaded('wallet'));
        self::assertFalse($buyer->wallet->exists);

        $transactions = $transactionHandler->apply([
            TransactionQuery::createDeposit($buyer, 101, null),
            TransactionQuery::createDeposit($buyer, 100, null),
            TransactionQuery::createDeposit($buyer, 100, null),
            TransactionQuery::createDeposit($buyer, 100, null),
            TransactionQuery::createWithdraw($buyer, 400, null),
            TransactionFloatQuery::createDeposit($buyer, 2.00, null),
            TransactionFloatQuery::createWithdraw($buyer, 2.00, null),
        ]);

        self::assertSame(1, $buyer->balanceInt);
        self::assertCount(7, $transactions);

        self::assertCount(
            5,
            array_filter($transactions, static fn ($t) => $t->type === Transaction::TYPE_DEPOSIT),
        );
        self::assertCount(
            2,
            array_filter($transactions, static fn ($t) => $t->type === Transaction::TYPE_WITHDRAW),
        );
    }
}
