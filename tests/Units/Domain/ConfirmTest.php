<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Exceptions\ConfirmedInvalid;
use ArsamMe\Wallet\Exceptions\UnconfirmedInvalid;
use ArsamMe\Wallet\Exceptions\WalletOwnerInvalid;
use ArsamMe\Wallet\Internal\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Internal\Service\DatabaseServiceInterface;
use ArsamMe\Wallet\Services\BookkeeperServiceInterface;
use ArsamMe\Wallet\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Factories\UserConfirmFactory;
use ArsamMe\Wallet\Test\Infra\Factories\UserFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\Models\User;
use ArsamMe\Wallet\Test\Infra\Models\UserConfirm;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class ConfirmTest extends TestCase {
    public function test_simple(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->deposit(1000, [
            'desc' => 'unconfirmed',
        ], false);
        self::assertTrue($transaction->getKey() > 0);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);

        $wallet->confirm($transaction);
        self::assertSame($transaction->amountInt, (int) app(BookkeeperServiceInterface::class)->amount($wallet));
        self::assertSame($transaction->amountInt, (int) app(RegulatorServiceInterface::class)->amount($wallet));
        self::assertSame(0, (int) app(RegulatorServiceInterface::class)->diff($wallet));
        self::assertSame($transaction->amountInt, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);
    }

    public function test_safe(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->forceWithdraw(1000, [
            'desc' => 'unconfirmed',
        ], false);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);
        self::assertTrue($transaction->getKey() > 0);

        $wallet->safeConfirm($transaction);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);
    }

    public function test_safe_confirmed_invalid(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->forceWithdraw(1000, [
            'desc' => 'confirmed',
        ]);

        self::assertSame(-1000, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);
        self::assertTrue($transaction->getKey() > 0);

        self::assertTrue($wallet->safeConfirm($transaction));
        self::assertSame(-1000, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);
    }

    public function test_safe_reset_confirm(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->forceWithdraw(1000, [
            'desc' => 'confirmed',
        ]);
        self::assertSame(-1000, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);

        $wallet->safeResetConfirm($transaction);
        self::assertSame(0, (int) app(BookkeeperServiceInterface::class)->amount($wallet));
        self::assertSame(0, (int) app(RegulatorServiceInterface::class)->amount($wallet));
        self::assertSame(0, (int) app(RegulatorServiceInterface::class)->diff($wallet));
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);
    }

    /**
     * @see https://github.com/ArsamMe/laravel-wallet/issues/134
     */
    public function test_withdraw(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;
        $wallet->deposit(100);

        self::assertSame(100, $wallet->balanceInt);

        $transaction = $wallet->withdraw(50, [
            'desc' => 'unconfirmed',
        ], false);
        self::assertSame(100, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);
    }

    public function test_force(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->forceWithdraw(1000, [
            'desc' => 'unconfirmed',
        ], false);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);

        $wallet->forceConfirm($transaction);
        self::assertSame($transaction->amountInt, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);
    }

    public function test_force_confirmed_invalid(): void {
        $this->expectException(ConfirmedInvalid::class);
        $this->expectExceptionCode(ExceptionInterface::CONFIRMED_INVALID);
        $this->expectExceptionMessageStrict(trans('wallet::errors.confirmed_invalid'));

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->forceWithdraw(1000);
        self::assertSame(-1000, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);

        $wallet->forceConfirm($transaction);
    }

    public function test_unconfirmed(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->forceWithdraw(1000, [
            'desc' => 'confirmed',
        ]);
        self::assertSame(-1000, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);

        $wallet->resetConfirm($transaction);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);
    }

    public function test_confirmed_invalid(): void {
        $this->expectException(ConfirmedInvalid::class);
        $this->expectExceptionCode(ExceptionInterface::CONFIRMED_INVALID);
        $this->expectExceptionMessageStrict(trans('wallet::errors.confirmed_invalid'));

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->deposit(1000);
        self::assertSame(1000, $wallet->balanceInt);
        self::assertTrue($transaction->confirmed);

        $wallet->confirm($transaction);
    }

    public function test_unconfirmed_invalid(): void {
        $this->expectException(UnconfirmedInvalid::class);
        $this->expectExceptionCode(ExceptionInterface::UNCONFIRMED_INVALID);
        $this->expectExceptionMessageStrict(trans('wallet::errors.unconfirmed_invalid'));

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->deposit(1000, null, false);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);

        $wallet->resetConfirm($transaction);
    }

    public function test_safe_unconfirmed(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        $wallet = $buyer->wallet;

        self::assertSame(0, $wallet->balanceInt);

        $transaction = $wallet->deposit(1000, null, false);
        self::assertSame(0, $wallet->balanceInt);
        self::assertFalse($transaction->confirmed);
        self::assertTrue($wallet->safeResetConfirm($transaction));
    }

    public function test_safe_unconfirmed_wallet_owner_invalid(): void {
        /**
         * @var Buyer $buyer1
         * @var Buyer $buyer2
         **/
        [$buyer1, $buyer2] = BuyerFactory::times(2)->create();
        $wallet1 = $buyer1->wallet;
        $wallet2 = $buyer2->wallet;

        self::assertTrue($wallet1->saveOrFail());
        self::assertTrue($wallet2->saveOrFail());

        self::assertSame(0, $wallet1->balanceInt);
        self::assertSame(0, $wallet2->balanceInt);

        $transaction1 = $wallet1->deposit(1000, null, true);
        self::assertSame(1000, $wallet1->balanceInt);
        self::assertTrue($transaction1->confirmed);

        self::assertFalse($wallet2->safeResetConfirm($transaction1));
        self::assertSame(1000, $wallet1->balanceInt);
        self::assertTrue($transaction1->confirmed);
    }

    public function test_wallet_owner_invalid(): void {
        $this->expectException(WalletOwnerInvalid::class);
        $this->expectExceptionCode(ExceptionInterface::WALLET_OWNER_INVALID);
        $this->expectExceptionMessageStrict(trans('wallet::errors.owner_invalid'));

        /**
         * @var Buyer $first
         * @var Buyer $second
         */
        [$first, $second] = BuyerFactory::times(2)->create();
        $firstWallet = $first->wallet;
        $secondWallet = $second->wallet;

        self::assertSame(0, $firstWallet->balanceInt);

        $transaction = $firstWallet->deposit(1000, [
            'desc' => 'unconfirmed',
        ], false);
        self::assertSame(0, $firstWallet->balanceInt);
        self::assertFalse($transaction->confirmed);

        $secondWallet->confirm($transaction);
    }

    public function test_force_wallet_owner_invalid(): void {
        $this->expectException(WalletOwnerInvalid::class);
        $this->expectExceptionCode(ExceptionInterface::WALLET_OWNER_INVALID);
        $this->expectExceptionMessageStrict(trans('wallet::errors.owner_invalid'));

        /**
         * @var Buyer $first
         * @var Buyer $second
         */
        [$first, $second] = BuyerFactory::times(2)->create();
        $firstWallet = $first->wallet;
        $secondWallet = $second->wallet;

        self::assertSame(0, $firstWallet->balanceInt);

        $transaction = $firstWallet->deposit(1000, [
            'desc' => 'unconfirmed',
        ], false);
        self::assertSame(0, $firstWallet->balanceInt);
        self::assertFalse($transaction->confirmed);

        $secondWallet->forceConfirm($transaction);
    }

    public function test_user_confirm(): void {
        /** @var UserConfirm $userConfirm */
        $userConfirm = UserConfirmFactory::new()->create();
        $transaction = $userConfirm->deposit(100, null, false);
        self::assertSame($transaction->wallet->getKey(), $userConfirm->wallet->getKey());
        self::assertSame((int) $transaction->payable_id, (int) $userConfirm->getKey());
        self::assertInstanceOf(UserConfirm::class, $transaction->payable);
        self::assertFalse($transaction->confirmed);

        self::assertTrue($userConfirm->confirm($transaction));
        self::assertTrue($transaction->confirmed);
    }

    public function test_confirm_without_wallet(): void {
        /** @var UserConfirm $userConfirm */
        $userConfirm = UserConfirmFactory::new()->create();
        $userConfirm->deposit(10000);

        $transaction = $userConfirm->withdraw(1000, null, false);
        self::assertFalse($transaction->confirmed);
        self::assertSame(10000, $userConfirm->balanceInt);

        self::assertTrue($transaction->wallet->confirm($transaction));
        self::assertSame(9000, $userConfirm->balanceInt);
    }

    public function test_user_confirm_by_wallet(): void {
        /** @var UserConfirm $userConfirm */
        $userConfirm = UserConfirmFactory::new()->create();
        $transaction = $userConfirm->wallet->deposit(100, null, false);
        self::assertSame($transaction->wallet->getKey(), $userConfirm->wallet->getKey());
        self::assertSame((int) $transaction->payable_id, (int) $userConfirm->getKey());
        self::assertInstanceOf(UserConfirm::class, $transaction->payable);
        self::assertFalse($transaction->confirmed);

        self::assertTrue($userConfirm->confirm($transaction));
        self::assertTrue($transaction->confirmed);
        self::assertTrue($userConfirm->resetConfirm($transaction));
        self::assertFalse($transaction->confirmed);
        self::assertTrue($userConfirm->wallet->confirm($transaction));
        self::assertTrue($transaction->confirmed);
    }

    public function test_transaction_reset_confirm_wallet_owner_invalid(): void {
        $this->expectException(WalletOwnerInvalid::class);
        $this->expectExceptionCode(ExceptionInterface::WALLET_OWNER_INVALID);
        $this->expectExceptionMessageStrict(trans('wallet::errors.owner_invalid'));

        /**
         * @var User $user1
         * @var User $user2
         */
        [$user1, $user2] = UserFactory::times(2)->create();
        $user1->deposit(1000);

        self::assertSame(1000, $user1->balanceInt);

        $transfer = $user1->transfer($user2, 500);
        $user1->wallet->resetConfirm($transfer->deposit);
    }

    public function test_transaction_reset_confirm_success(): void {
        /**
         * @var User $user1
         * @var User $user2
         */
        [$user1, $user2] = UserFactory::times(2)->create();
        $user1->deposit(1000);

        self::assertSame(1000, $user1->balanceInt);
        app(DatabaseServiceInterface::class)->transaction(static function () use ($user1, $user2) {
            $transfer = $user1->transfer($user2, 500);
            self::assertTrue($user2->wallet->resetConfirm($transfer->deposit)); // confirm => false
        });

        /** @var string $sum1 */
        $sum1 = $user1->transactions()
            ->sum('amount');
        /** @var string $sum2 */
        $sum2 = $user2->transactions()
            ->sum('amount');

        self::assertSame(500, (int) $sum1);
        self::assertSame(500, (int) $sum2);

        self::assertSame(500, $user1->balanceInt);
        self::assertSame(0, $user2->balanceInt);
    }
}
