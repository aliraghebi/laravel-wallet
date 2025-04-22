<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\External\Dto\Extra;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Services\ExchangeService;
use ArsamMe\Wallet\Services\ExchangeServiceInterface;
use ArsamMe\Wallet\Services\RegulatorServiceInterface;
use ArsamMe\Wallet\Test\Infra\Factories\UserMultiFactory;
use ArsamMe\Wallet\Test\Infra\Models\UserMulti;
use ArsamMe\Wallet\Test\Infra\Services\ExchangeUsdToBtcService;
use ArsamMe\Wallet\Test\Infra\TestCase;
use Illuminate\Support\Str;

/**
 * @internal
 */
final class ExchangeTest extends TestCase {
    public function test_simple(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'Мои рубли',
            'slug' => 'rub',
        ]);

        self::assertSame(0, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $rub->deposit(10000);

        self::assertSame(10000, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $transfer = $rub->exchange($usd, 10000);
        self::assertSame(0, $rub->balanceInt);
        self::assertSame(147, $usd->balanceInt);
        self::assertSame(1.47, (float) $usd->balanceFloat); // $1.47
        self::assertSame(0, (int) $transfer->fee);
        self::assertSame(Transfer::STATUS_EXCHANGE, $transfer->status);

        $transfer = $usd->exchange($rub, $usd->balanceInt);
        self::assertSame(0, $usd->balanceInt);
        self::assertSame(9938, $rub->balanceInt);
        self::assertSame(Transfer::STATUS_EXCHANGE, $transfer->status);
    }

    public function test_safe(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'My USD',
            'slug' => 'usd',
        ]);

        $rub = $user->createWallet([
            'name' => 'Мои рубли',
            'slug' => 'rub',
        ]);

        self::assertSame(0, $rub->balanceInt);
        self::assertSame(0, $usd->balanceInt);

        $transfer = $rub->safeExchange($usd, 10000);
        self::assertNull($transfer);
    }

    public function test_exchange_class(): void {
        $service = app(ExchangeService::class);

        self::assertSame('1', $service->convertTo('USD', 'EUR', 1));
        self::assertSame('5', $service->convertTo('USD', 'EUR', 5));
        self::assertSame('27', $service->convertTo('USD', 'EUR', 27));
    }

    public function test_rate(): void {
        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'Dollar USA',
            'slug' => 'my-usd',
            'meta' => [
                'currency' => 'USD',
            ],
        ]);
        self::assertSame($usd->slug, 'my-usd');
        self::assertSame($usd->currency, 'USD');
        self::assertSame($usd->holder_id, $user->getKey());
        self::assertInstanceOf($usd->holder_type, $user);

        $rub = $user->createWallet([
            'name' => 'RUB',
        ]);
        self::assertSame($rub->slug, 'rub');
        self::assertSame($rub->currency, 'RUB');
        self::assertSame($rub->holder_id, $user->getKey());
        self::assertInstanceOf($rub->holder_type, $user);

        $superWallet = $user->createWallet([
            'name' => 'Super Wallet',
        ]);
        self::assertSame($superWallet->slug, Str::slug('Super Wallet'));
        self::assertSame($superWallet->currency, Str::upper(Str::slug('Super Wallet')));
        self::assertSame($superWallet->holder_id, $user->getKey());
        self::assertInstanceOf($superWallet->holder_type, $user);

        $rate = app(ExchangeServiceInterface::class)
            ->convertTo($usd->currency, $rub->currency, 1000);

        self::assertSame(67610., (float) $rate);
    }

    public function test_exchange(): void {
        $rate = app(ExchangeServiceInterface::class)
            ->convertTo('USD', 'RUB', 1);

        self::assertSame(67.61, (float) $rate);

        $rate = app(ExchangeServiceInterface::class)
            ->convertTo('RUB', 'USD', 1);

        self::assertSame(1 / 67.61, (float) $rate);
    }

    public function test_exchange_usd_to_btc(): void {
        app()->bind(ExchangeServiceInterface::class, ExchangeUsdToBtcService::class);

        $rate = (float) app(ExchangeServiceInterface::class)
            ->convertTo('USD', 'BTC', 1);

        self::assertSame(0.004636, $rate);

        /** @var UserMulti $user */
        $user = UserMultiFactory::new()->create();
        $usd = $user->createWallet([
            'name' => 'Dollar USA',
            'slug' => 'my-usd',
            'decimal_places' => 8,
            'meta' => [
                'currency' => 'USD',
            ],
        ]);
        $btc = $user->createWallet([
            'name' => 'Bitcoin',
            'slug' => 'my-btc',
            'decimal_places' => 8,
            'meta' => [
                'currency' => 'BTC',
            ],
        ]);

        $usd->depositFloat(100.);
        self::assertSame(100., $usd->balanceFloatNum);
        self::assertSame(10000000000, $usd->balanceInt);

        $usd->exchange($btc, 10000000000, new Extra(
            deposit: [
                'amountFloat' => 100 * $rate,
            ],
            withdraw: [
                'amountFloat' => -100.,
            ],
        ));

        $regulatorService = app(RegulatorServiceInterface::class);
        $regulatorService->forget($usd);
        $regulatorService->forget($btc);

        // get data from database
        $usd->refresh();
        $btc->refresh();

        self::assertSame(0, $usd->balanceInt);
        self::assertSame(100 * $rate, $btc->balanceFloatNum);
    }
}
