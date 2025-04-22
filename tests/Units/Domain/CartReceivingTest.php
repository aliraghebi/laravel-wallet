<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Objects\Cart;
use ArsamMe\Wallet\Services\PurchaseServiceInterface;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Factories\ItemFactory;
use ArsamMe\Wallet\Test\Infra\Factories\ItemMetaFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\Models\Item;
use ArsamMe\Wallet\Test\Infra\Models\ItemMeta;
use ArsamMe\Wallet\Test\Infra\PackageModels\Transaction;
use ArsamMe\Wallet\Test\Infra\TestCase;
use Illuminate\Database\Eloquent\Collection;

use function count;

/**
 * @internal
 */
final class CartReceivingTest extends TestCase {
    public function test_cart_meta(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemMeta $product */
        $product = ItemMetaFactory::new()->create([
            'quantity' => 1,
        ]);

        $expected = 'pay';

        $payment = $buyer->createWallet([
            'name' => 'Dollar',
            'meta' => [
                'currency' => 'USD',
            ],
        ]);

        $receiving = $product->createWallet([
            'name' => 'Dollar',
            'meta' => [
                'currency' => 'USD',
            ],
        ]);

        $cart = app(Cart::class)
            ->withItem($product, receiving: $receiving)
            ->withMeta([
                'type' => $expected,
            ]);

        $amount = $cart->getTotal($buyer);

        self::assertSame(0, $buyer->balanceInt);
        self::assertNotNull($payment->deposit($amount));

        $transfers = $payment->payCart($cart);
        self::assertCount(1, $transfers);

        $transfer = current($transfers);

        /** @var Transaction[] $transactions */
        $transactions = [$transfer->deposit, $transfer->withdraw];
        foreach ($transactions as $transaction) {
            self::assertSame($product->price, $transaction->meta['price']);
            self::assertSame($product->name, $transaction->meta['name']);
            self::assertSame($expected, $transaction->meta['type']);
        }

        self::assertSame((int) $amount, $receiving->balanceInt);
        self::assertSame('USD', $receiving->currency);

        self::assertSame(0, $payment->balanceInt);
        self::assertSame('USD', $payment->currency);
    }

    public function test_pay(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var Collection<int, Item> $products */
        $products = ItemFactory::times(10)->create([
            'quantity' => 1,
        ]);

        $cart = app(Cart::class);
        foreach ($products as $product) {
            $receiving = $product->createWallet([
                'name' => 'Dollar',
                'meta' => [
                    'currency' => 'USD',
                ],
            ]);

            $cart = $cart->withItem($product, pricePerItem: 1, receiving: $receiving);
        }

        self::assertCount(10, $cart->getItems());

        foreach ($cart->getItems() as $product) {
            /** @var Item $product */
            self::assertSame(0, $product->getWallet('dollar')?->balanceInt);
        }

        $payment = $buyer->createWallet([
            'name' => 'Dollar',
            'meta' => [
                'currency' => 'USD',
            ],
        ]);

        $payment->deposit($cart->getTotal($buyer));

        self::assertSame(10, $payment->balanceInt);

        $transfers = $payment->payCart($cart);

        self::assertCount(count($cart), $transfers);
        self::assertTrue((bool) app(PurchaseServiceInterface::class)->already($payment, $cart->getBasketDto()));
        self::assertSame(0, $payment->balanceInt);

        foreach ($transfers as $transfer) {
            self::assertSame(Transfer::STATUS_PAID, $transfer->status);
            self::assertNull($transfer->status_last);
        }

        foreach ($cart->getItems() as $product) {
            /** @var Item $product */
            self::assertSame(1, $product->getWallet('dollar')?->balanceInt);
        }

        self::assertTrue($payment->refundCart($cart));
        foreach ($transfers as $transfer) {
            $transfer->refresh();
            self::assertSame(Transfer::STATUS_REFUND, $transfer->status);
            self::assertSame(Transfer::STATUS_PAID, $transfer->status_last);
        }

        self::assertSame(10, $payment->balanceInt);
    }
}
