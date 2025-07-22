<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Test\Models\User;
use AliRaghebi\Wallet\Test\TestCase;
use Illuminate\Database\Eloquent\Collection;

/**
 * @internal
 */
final class EagerLoadingTest extends TestCase {
    public function test_wallet_relations(): void {
        $expected = [];

        $users = $this->createUser(10);
        foreach ($users as $user) {
            self::assertTrue($user->wallet->relationLoaded('holder'));
            $user->deposit(100);
            $expected[] = $user->wallet->uuid;
        }

        /** @var Collection<int, User> $users */
        $users = User::with('wallet.walletTransactions')
            ->whereIn('id', collect($users)->pluck('id')->toArray())
            ->paginate(10);

        $uuids = [];
        $balances = [];
        foreach ($users as $user) {
            self::assertTrue($user->relationLoaded('wallet'));
            self::assertTrue($user->wallet->relationLoaded('holder'));
            self::assertTrue($user->wallet->relationLoaded('walletTransactions'));

            $uuids[] = $user->wallet->uuid;
            $balances[] = $user->wallet->balance_int;
        }

        self::assertCount(10, array_unique($uuids));
        self::assertCount(1, array_unique($balances));
        self::assertEquals($expected, $uuids);
    }

    public function test_transfer_relations(): void {
        [$user1, $user2] = $this->createUser(2);

        $user1->deposit(1000);
        self::assertSame(1000, $user1->balanceInt);

        $transfer = $user1->transfer($user2, 500);
        self::assertTrue($transfer->relationLoaded('withdraw'));
        self::assertTrue($transfer->relationLoaded('deposit'));

        self::assertTrue($transfer->relationLoaded('from'));
        self::assertTrue($transfer->relationLoaded('to'));

        self::assertTrue($user1->wallet->is($transfer->from));
        self::assertTrue($user2->wallet->is($transfer->to));
    }

    public function test_multi_wallets(): void {
        $multi = $this->createUser();
        $multi->createWallet(name: 'Hello');
        $multi->createWallet(name : 'World');

        $user = User::with('wallets.walletTransactions')->find($multi->getKey());
        self::assertTrue($user->relationLoaded('wallets'));
        self::assertNotEmpty($user->wallets);

        foreach ($user->wallets as $wallet) {
            self::assertTrue($wallet->relationLoaded('walletTransactions'));
        }

        self::assertNotNull($user->getWallet('hello'));
        self::assertNotNull($user->getWallet('world'));
        self::assertTrue($user->getWallet('hello')->relationLoaded('holder'));
        self::assertTrue($user->is($user->getWallet('hello')->holder));
    }
}
