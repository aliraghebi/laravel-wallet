<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\External\Dto\Extra;
use ArsamMe\Wallet\External\Dto\Option;
use ArsamMe\Wallet\Internal\Service\IdentifierFactoryServiceInterface;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Factories\UserMultiFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\Models\UserMulti;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class ExtraTest extends TestCase {
    public function test_extra_transfer_withdraw(): void {
        /** @var Buyer $user1 */
        /** @var Buyer $user2 */
        [$user1, $user2] = BuyerFactory::times(2)->create();

        $user1->deposit(1000);
        self::assertSame(1000, $user1->balanceInt);

        $transfer = $user1->transfer(
            $user2,
            500,
            new Extra(
                deposit: [
                    'type' => 'extra-deposit',
                ],
                withdraw: new Option(
                    [
                        'type' => 'extra-withdraw',
                    ],
                    false
                ),
                extra: [
                    'msg' => 'hello world',
                ],
            )
        );

        self::assertSame(1000, $user1->balanceInt);
        self::assertSame(500, $user2->balanceInt);
        self::assertNotNull($transfer);

        self::assertSame([
            'type' => 'extra-deposit',
        ], $transfer->deposit->meta);
        self::assertSame([
            'type' => 'extra-withdraw',
        ], $transfer->withdraw->meta);
        self::assertSame([
            'msg' => 'hello world',
        ], $transfer->extra);
    }

    public function test_extra_transfer_uuid_fixed(): void {
        /** @var Buyer $user1 */
        /** @var Buyer $user2 */
        [$user1, $user2] = BuyerFactory::times(2)->create();

        $user1->deposit(1000);
        self::assertSame(1000, $user1->balanceInt);

        $uuidFactory = app(IdentifierFactoryServiceInterface::class);
        $depositUuid = $uuidFactory->generate();
        $withdrawUuid = $uuidFactory->generate();
        $transferUuid = $uuidFactory->generate();

        $transfer = $user1->transfer(
            $user2,
            500,
            new Extra(
                deposit: new Option(
                    [
                        'type' => 'extra-deposit',
                    ],
                    uuid: $depositUuid
                ),
                withdraw: new Option(
                    [
                        'type' => 'extra-withdraw',
                    ],
                    false,
                    $withdrawUuid
                ),
                uuid: $transferUuid
            )
        );

        self::assertSame(1000, $user1->balanceInt);
        self::assertSame(500, $user2->balanceInt);
        self::assertNotNull($transfer);

        self::assertSame($transferUuid, $transfer->uuid);
        self::assertSame($depositUuid, $transfer->deposit->uuid);
        self::assertSame($withdrawUuid, $transfer->withdraw->uuid);

        self::assertSame([
            'type' => 'extra-deposit',
        ], $transfer->deposit->meta);
        self::assertSame([
            'type' => 'extra-withdraw',
        ], $transfer->withdraw->meta);
    }

    public function test_extra_transfer_deposit(): void {
        /** @var Buyer $user1 */
        /** @var Buyer $user2 */
        [$user1, $user2] = BuyerFactory::times(2)->create();

        $user1->deposit(1000);
        self::assertSame(1000, $user1->balanceInt);

        $transfer = $user1->transfer(
            $user2,
            500,
            new Extra(
                deposit: new Option(
                    [
                        'type' => 'extra-deposit',
                    ],
                    false
                ),
                withdraw: [
                    'type' => 'extra-withdraw',
                ],
            )
        );

        self::assertSame(500, $user1->balanceInt);
        self::assertSame(0, $user2->balanceInt);
        self::assertNotNull($transfer);

        self::assertSame([
            'type' => 'extra-deposit',
        ], $transfer->deposit->meta);
        self::assertSame([
            'type' => 'extra-withdraw',
        ], $transfer->withdraw->meta);
    }

    public function test_extra_exchange_deposit(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'My RUB',
            'slug' => 'rub',
        ]);

        self::assertSame(0, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $rub->deposit(10_000);

        self::assertSame(10_000, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $transfer = $rub->exchange($usd, 10000, new Extra(
            deposit: [
                'message' => 'We credit to the dollar account',
            ],
            withdraw: new Option(
                [
                    'message' => 'Write off from the ruble account',
                ],
                false
            )
        ));

        self::assertSame(10_000, $rub->balanceInt);
        self::assertSame(147, $usd->balanceInt);
        self::assertSame(1.47, (float) $usd->balanceFloat); // $1.47
        self::assertSame(0, (int) $transfer->fee);
        self::assertSame(Transfer::STATUS_EXCHANGE, $transfer->status);
        self::assertSame([
            'message' => 'We credit to the dollar account',
        ], $transfer->deposit->meta);
        self::assertSame([
            'message' => 'Write off from the ruble account',
        ], $transfer->withdraw->meta);
    }

    public function test_extra_exchange_uuid_fixed(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'My RUB',
            'slug' => 'rub',
        ]);

        self::assertSame(0, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $rub->deposit(10_000);

        self::assertSame(10_000, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $uuidFactory = app(IdentifierFactoryServiceInterface::class);
        $depositUuid = $uuidFactory->generate();
        $withdrawUuid = $uuidFactory->generate();
        $transferUuid = $uuidFactory->generate();

        $transfer = $rub->exchange($usd, 10000, new Extra(
            deposit: new Option(
                [
                    'message' => 'We credit to the dollar account',
                ],
                uuid: $depositUuid
            ),
            withdraw: new Option(
                [
                    'message' => 'Write off from the ruble account',
                ],
                false,
                $withdrawUuid
            ),
            uuid: $transferUuid,
        ));

        self::assertSame($transferUuid, $transfer->uuid);
        self::assertSame($depositUuid, $transfer->deposit->uuid);
        self::assertSame($withdrawUuid, $transfer->withdraw->uuid);

        self::assertSame(10_000, $rub->balanceInt);
        self::assertSame(147, $usd->balanceInt);
        self::assertSame(1.47, (float) $usd->balanceFloat); // $1.47
        self::assertSame(0, (int) $transfer->fee);
        self::assertSame(Transfer::STATUS_EXCHANGE, $transfer->status);
        self::assertSame([
            'message' => 'We credit to the dollar account',
        ], $transfer->deposit->meta);
        self::assertSame([
            'message' => 'Write off from the ruble account',
        ], $transfer->withdraw->meta);
    }

    public function test_extra_exchange_withdraw(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'My RUB',
            'slug' => 'rub',
        ]);

        self::assertSame(0, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $rub->deposit(10_000);

        self::assertSame(10_000, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $transfer = $rub->exchange($usd, 10000, new Extra(
            deposit: new Option(
                [
                    'message' => 'We credit to the dollar account',
                ],
                false
            ),
            withdraw: [
                'message' => 'Write off from the ruble account',
            ],
        ));

        self::assertSame(0, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);
        self::assertSame(0, (int) $transfer->fee);
        self::assertSame(Transfer::STATUS_EXCHANGE, $transfer->status);
        self::assertSame([
            'message' => 'We credit to the dollar account',
        ], $transfer->deposit->meta);
        self::assertSame([
            'message' => 'Write off from the ruble account',
        ], $transfer->withdraw->meta);
    }

    public function test_pending_balances(): void {
        /** @var Buyer $user1 */
        /** @var Buyer $user2 */
        [$user1, $user2] = BuyerFactory::times(2)->create();

        $user1->deposit(1000);
        self::assertSame(1000, $user1->balanceInt);
        self::assertSame(0, $user2->balanceInt);

        $transfer = $user1->transfer($user2, 500, new Extra(
            deposit: new Option(null, confirmed: false),
            withdraw: null,
        ));

        self::assertNotNull($transfer);
        self::assertTrue($transfer->withdraw->confirmed);
        self::assertFalse($transfer->deposit->confirmed);

        self::assertSame(500, $user1->balanceInt);
        self::assertSame(0, $user2->balanceInt);

        self::assertTrue($user2->wallet->confirm($transfer->deposit)); // confirmed

        self::assertSame(500, $user2->balanceInt);
    }
}
