<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\External\Dto\Extra;
use ArsamMe\Wallet\External\Dto\Option;
use ArsamMe\Wallet\Internal\Service\IdentifierFactoryServiceInterface;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class BlockTest extends TestCase {
    /**
     * @see https://github.com/ArsamMe/laravel-wallet/issues/174
     * @see https://github.com/ArsamMe/laravel-wallet/issues/416
     */
    public function test_block_transfer(): void {
        /** @var Buyer $buyer1 */
        /** @var Buyer $buyer2 */
        [$buyer1, $buyer2] = BuyerFactory::times(2)->create();
        $uuidFactory = app(IdentifierFactoryServiceInterface::class);
        $idempotent = $uuidFactory->generate();

        $transfer = $buyer1->forceTransfer($buyer2, 500, new Extra(
            deposit: new Option(
                [
                    'type' => 'block',
                    'idempotent' => $idempotent,
                ],
                confirmed: false,
            ),
            withdraw: [
                'idempotent' => $idempotent,
            ],
        ));

        self::assertSame(-500, $buyer1->balanceInt);
        self::assertSame(0, $buyer2->balanceInt);

        self::assertTrue($transfer->from->is($buyer1->wallet));
        self::assertTrue($transfer->to->is($buyer2->wallet));

        self::assertSame(-500, $transfer->withdraw->amountInt);
        self::assertTrue($transfer->withdraw->confirmed);

        self::assertSame(500, $transfer->deposit->amountInt);
        self::assertFalse($transfer->deposit->confirmed);

        /** @var Transaction $transaction */
        $transaction = $buyer2->wallet->walletTransactions()
            ->where('meta->type', 'block')
            ->where('confirmed', false)
            ->first();

        self::assertTrue($transfer->deposit->is($transaction));
        self::assertTrue($buyer2->wallet->confirm($transaction));
    }
}
