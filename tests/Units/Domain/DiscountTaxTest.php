<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Exceptions\ProductEnded;
use ArsamMe\Wallet\Internal\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Models\Transaction;
use ArsamMe\Wallet\Models\Transfer;
use ArsamMe\Wallet\Models\Wallet;
use ArsamMe\Wallet\Services\DiscountServiceInterface;
use ArsamMe\Wallet\Services\TaxServiceInterface;
use ArsamMe\Wallet\Test\Infra\Factories\BuyerFactory;
use ArsamMe\Wallet\Test\Infra\Factories\ItemDiscountTaxFactory;
use ArsamMe\Wallet\Test\Infra\Models\Buyer;
use ArsamMe\Wallet\Test\Infra\Models\ItemDiscountTax;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class DiscountTaxTest extends TestCase {
    public function test_pay(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create();

        self::assertSame(0, $buyer->balanceInt);
        $fee = (int) app(TaxServiceInterface::class)->getFee($product, $product->getAmountProduct($buyer));
        $buyer->deposit($product->getAmountProduct($buyer) + $fee);

        self::assertSame($buyer->balanceInt, $product->getAmountProduct($buyer) + $fee);
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertSame(Transfer::STATUS_PAID, $transfer->status);

        self::assertSame($buyer->balanceInt, $product->getPersonalDiscount($buyer));

        self::assertSame((int) $transfer->discount, $product->getPersonalDiscount($buyer));

        self::assertSame((int) $transfer->fee, $fee);

        $withdraw = $transfer->withdraw;
        $deposit = $transfer->deposit;

        self::assertInstanceOf(Transaction::class, $withdraw);
        self::assertInstanceOf(Transaction::class, $deposit);

        self::assertInstanceOf(Buyer::class, $withdraw->payable);
        self::assertInstanceOf(ItemDiscountTax::class, $deposit->payable);

        self::assertSame($buyer->getKey(), $withdraw->payable->getKey());
        self::assertSame($product->getKey(), $deposit->payable->getKey());

        self::assertInstanceOf(Buyer::class, $transfer->from->holder);
        self::assertInstanceOf(Wallet::class, $transfer->from);
        self::assertInstanceOf(ItemDiscountTax::class, $transfer->to->holder);
        self::assertInstanceOf(Wallet::class, $transfer->to->wallet);

        self::assertSame($buyer->wallet->getKey(), $transfer->from->getKey());
        self::assertSame($buyer->getKey(), $transfer->from->holder->getKey());
        self::assertSame($product->wallet->getKey(), $transfer->to->getKey());
        self::assertSame($product->getKey(), $transfer->to->holder->getKey());
    }

    public function test_refund_personal_discount_and_tax(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create();

        self::assertSame($buyer->balanceInt, 0);
        $discount = app(DiscountServiceInterface::class)->getDiscount($buyer, $product);
        $fee = (int) app(TaxServiceInterface::class)->getFee($product, $product->getAmountProduct($buyer));
        $buyer->deposit($product->getAmountProduct($buyer) + $fee - $discount);

        self::assertSame($buyer->balanceInt, (int) ($product->getAmountProduct($buyer) + $fee - $discount));
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertSame($transfer->status, Transfer::STATUS_PAID);

        self::assertSame((int) $transfer->discount, $product->getPersonalDiscount($buyer));

        self::assertSame((int) $transfer->fee, $fee);

        self::assertTrue($buyer->refund($product));
        self::assertSame(
            $buyer->balanceInt,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertSame($product->balanceInt, 0);

        $transfer->refresh();
        self::assertSame($transfer->status, Transfer::STATUS_REFUND);

        self::assertFalse($buyer->safeRefund($product));
        self::assertSame(
            $buyer->balanceInt,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertNull($buyer->safePay($product));
        $buyer->deposit($fee);
        $transfer = $buyer->pay($product);
        self::assertNotNull($transfer);
        self::assertSame(0, $buyer->balanceInt);
        self::assertSame(
            $product->balanceInt,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertSame($transfer->status, Transfer::STATUS_PAID);

        self::assertTrue($buyer->refund($product));
        self::assertSame(
            $buyer->balanceInt,
            $product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)
        );

        self::assertSame($product->balanceInt, 0);

        $transfer->refresh();
        self::assertSame($transfer->status, Transfer::STATUS_REFUND);
    }

    public function test_force_refund(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create();

        self::assertSame(0, $buyer->balanceInt);
        $discount = app(DiscountServiceInterface::class)->getDiscount($buyer, $product);
        $fee = (int) app(TaxServiceInterface::class)->getFee($product, $product->getAmountProduct($buyer));
        $buyer->deposit($product->getAmountProduct($buyer) + $fee - $discount);

        $paidPrice = $buyer->balance;
        self::assertSame($buyer->balanceInt, $product->getAmountProduct($buyer) + $fee - $discount);

        $transfer = $buyer->pay($product);
        self::assertSame(0, $buyer->balanceInt);

        self::assertSame($product->balanceInt, -$transfer->withdraw->amountInt - $fee);

        self::assertSame((int) $transfer->discount, $product->getPersonalDiscount($buyer));

        self::assertSame((int) $transfer->fee, $fee);

        $product->withdraw($product->balance);
        self::assertSame($product->balanceInt, 0);

        self::assertFalse($buyer->safeRefund($product));
        self::assertTrue($buyer->forceRefund($product));

        self::assertSame((int) $paidPrice - $fee, -$product->balanceInt);
        self::assertSame(
            $product->balanceInt,
            -($product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer))
        );

        self::assertSame((int) $paidPrice - $fee, $buyer->balanceInt);
        $product->deposit(-$product->balanceInt);
        $buyer->withdraw($buyer->balance);

        self::assertSame($product->balanceInt, 0);
        self::assertSame($buyer->balanceInt, 0);
    }

    public function test_out_of_stock(): void {
        $this->expectException(ProductEnded::class);
        $this->expectExceptionCode(ExceptionInterface::PRODUCT_ENDED);
        $this->expectExceptionMessageStrict(trans('wallet::errors.product_stock'));

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        $fee = (int) app(TaxServiceInterface::class)->getFee($product, $product->getAmountProduct($buyer));
        $buyer->deposit($product->getAmountProduct($buyer) + $fee);
        $buyer->pay($product);
        $buyer->pay($product);
    }

    public function test_force_pay(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertSame($buyer->balanceInt, 0);
        $buyer->forcePay($product);

        $fee = (int) app(TaxServiceInterface::class)->getFee($product, $product->getAmountProduct($buyer));
        self::assertSame(
            $buyer->balanceInt,
            -($product->getAmountProduct($buyer) - $product->getPersonalDiscount($buyer)) - $fee
        );

        $buyer->deposit(-$buyer->balanceInt);
        self::assertSame($buyer->balanceInt, 0);
    }

    public function test_pay_free_and_refund(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertSame($buyer->balanceInt, 0);

        $transfer = $buyer->payFree($product);
        self::assertSame($transfer->deposit->type, Transaction::TYPE_DEPOSIT);
        self::assertSame($transfer->withdraw->type, Transaction::TYPE_WITHDRAW);

        self::assertSame($buyer->balanceInt, 0);
        self::assertSame($product->balanceInt, 0);

        $buyer->refund($product);
        self::assertSame($buyer->balanceInt, 0);
        self::assertSame($product->balanceInt, 0);
    }

    public function test_free_pay(): void {
        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        $buyer->forceWithdraw(1000);
        self::assertSame($buyer->balanceInt, -1000);

        $transfer = $buyer->payFree($product);
        self::assertSame($transfer->deposit->type, Transaction::TYPE_DEPOSIT);
        self::assertSame($transfer->withdraw->type, Transaction::TYPE_WITHDRAW);

        self::assertSame((int) $transfer->discount, $product->getPersonalDiscount($buyer));

        self::assertSame(0, (int) $transfer->fee);

        self::assertSame($buyer->balanceInt, -1000);
        self::assertSame($product->balanceInt, 0);

        $buyer->refund($product);
        self::assertSame($buyer->balanceInt, -1000);
        self::assertSame($product->balanceInt, 0);
    }

    public function test_pay_free_out_of_stock(): void {
        $this->expectException(ProductEnded::class);
        $this->expectExceptionCode(ExceptionInterface::PRODUCT_ENDED);
        $this->expectExceptionMessageStrict(trans('wallet::errors.product_stock'));

        /** @var Buyer $buyer */
        $buyer = BuyerFactory::new()->create();
        /** @var ItemDiscountTax $product */
        $product = ItemDiscountTaxFactory::new()->create([
            'quantity' => 1,
        ]);

        self::assertNotNull($buyer->payFree($product));
        $buyer->payFree($product);
    }
}
