<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Services\TaxServiceInterface;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Factories\ItemDiscountTaxFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\Models\ItemDiscountTax;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class GiftDiscountTaxTest extends TestCase {
    public function test_gift(): void {
        /**
         * @var Buyer $first
         * @var Buyer $second
         */
        [$first, $second] = BuyerFactory::times(2)->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertSame(0, $first->balanceInt);
        self::assertSame(0, $second->balanceInt);

        $fee = (int) app(TaxServiceInterface::class)->getFee(
            $product,
            $product->getAmountProduct($first) - $product->getPersonalDiscount($first)
        );

        $first->deposit($product->getAmountProduct($first) + $fee);
        self::assertSame($first->balanceInt, $product->getAmountProduct($first) + $fee);

        $transfer = $first->wallet->gift($second, $product);
        self::assertSame($first->balanceInt, $product->getPersonalDiscount($first));
        self::assertSame($second->balanceInt, 0);
        self::assertNull($first->paid($product, true));
        self::assertNotNull($second->paid($product, true));
        self::assertNull($second->wallet->paid($product));
        self::assertNotNull($second->wallet->paid($product, true));
        self::assertSame($transfer->status, Transfer::STATUS_GIFT);
    }

    public function test_refund(): void {
        /**
         * @var Buyer $first
         * @var Buyer $second
         */
        [$first, $second] = BuyerFactory::times(2)->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertSame($first->balanceInt, 0);
        self::assertSame($second->balanceInt, 0);

        $fee = (int) app(TaxServiceInterface::class)->getFee(
            $product,
            $product->getAmountProduct($first) - $product->getPersonalDiscount($first)
        );

        $first->deposit($product->getAmountProduct($first) + $fee);
        self::assertSame($first->balanceInt, $product->getAmountProduct($first) + $fee);

        $transfer = $first->wallet->gift($second, $product);
        self::assertSame($first->balance, (string) $product->getPersonalDiscount($first));
        self::assertSame($second->balanceInt, 0);
        self::assertSame($transfer->status, Transfer::STATUS_GIFT);

        $first->withdraw($product->getPersonalDiscount($first));
        self::assertSame($first->balanceInt, 0);

        self::assertFalse($second->wallet->safeRefund($product));
        self::assertTrue($second->wallet->refundGift($product));

        self::assertSame(
            $first->balanceInt,
            $product->getAmountProduct($first) - $product->getPersonalDiscount($first)
        );

        $first->withdraw($first->balance);
        self::assertSame($first->balanceInt, 0);
        self::assertSame($second->balanceInt, 0);

        self::assertNull($second->wallet->safeGift($first, $product));

        $secondFee = (int) app(TaxServiceInterface::class)->getFee(
            $product,
            $product->getAmountProduct($second) - $product->getPersonalDiscount($second)
        );

        $transfer = $second->wallet->forceGift($first, $product);
        self::assertNotNull($transfer);
        self::assertSame($transfer->status, Transfer::STATUS_GIFT);

        self::assertSame(
            $second->balanceInt,
            -(($product->getAmountProduct($second) + $secondFee) - $product->getPersonalDiscount($second))
        );

        $second->deposit(-$second->balanceInt);
        self::assertSame($second->balanceInt, 0);
        self::assertSame($first->balanceInt, 0);

        $product->withdraw($product->balance);
        self::assertSame($product->balanceInt, 0);

        self::assertFalse($first->safeRefundGift($product));
        self::assertTrue($first->forceRefundGift($product));

        self::assertSame($second->balanceInt, -$product->balanceInt);

        self::assertSame(
            $product->balanceInt,
            -($product->getAmountProduct($second) - $product->getPersonalDiscount($second))
        );

        self::assertSame(
            $second->balanceInt,
            $product->getAmountProduct($second) - $product->getPersonalDiscount($second)
        );

        $second->withdraw($second->balance);
        self::assertSame($second->balanceInt, 0);
    }
}
