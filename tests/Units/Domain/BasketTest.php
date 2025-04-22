<?php

namespace ArsamMe\Wallet\Test\Units\Domain;

use ArsamMe\Wallet\Internal\Dto\BasketDto;
use ArsamMe\Wallet\Internal\Dto\ItemDto;
use ArsamMe\Wallet\Internal\Dto\ItemDtoInterface;
use ArsamMe\Wallet\Test\Infra\Models\Item;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class BasketTest extends TestCase {
    public function test_count(): void {
        $item = new Item;
        $productDto1 = new ItemDto($item, 24, null, null);
        $productDto2 = new ItemDto($item, 26, null, null);
        $basket = new BasketDto([$productDto1, $productDto2], [], null);

        self::assertEmpty($basket->meta());
        self::assertSame(2, $basket->count());

        $items = $basket->items();
        self::assertNotFalse(current($items));
        self::assertSame(0, key($items));
        self::assertSame(24, current($items)->count());
        self::assertNotFalse(next($items));
        self::assertSame(26, current($items)->count());
        self::assertSame(1, key($items));
    }

    public function test_meta(): void {
        /** @var non-empty-array<ItemDtoInterface> $items */
        $items = [];

        $basket1 = new BasketDto($items, [], null);
        self::assertEmpty($basket1->meta());

        $basket2 = new BasketDto($items, [
            'hello' => 'world',
        ], null);
        self::assertSame([
            'hello' => 'world',
        ], $basket2->meta());
    }

    public function test_extra(): void {
        /** @var non-empty-array<ItemDtoInterface> $items */
        $items = [];

        $basket1 = new BasketDto($items, [], null);
        self::assertNull($basket1->extra());

        $basket2 = new BasketDto($items, [], [
            'hello' => 'world',
        ]);
        self::assertSame([
            'hello' => 'world',
        ], $basket2->extra());
    }

    public function test_empty(): void {
        /** @var non-empty-array<ItemDtoInterface> $items */
        $items = [];

        $basket = new BasketDto($items, [], null);
        self::assertEmpty($basket->items());
        self::assertEmpty($basket->meta());
        self::assertSame(0, $basket->count());
    }
}
