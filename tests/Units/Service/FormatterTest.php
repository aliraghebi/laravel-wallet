<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\Internal\Exceptions\ExceptionInterface;
use ArsamMe\Wallet\Services\FormatterServiceInterface;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class FormatterTest extends TestCase {
    /**
     * @throws ExceptionInterface
     */
    public function test_float_value_d_p3(): void {
        $result = app(FormatterServiceInterface::class)->floatValue('12345', 3);

        self::assertSame('12.345', $result);
    }

    /**
     * @throws ExceptionInterface
     */
    public function test_float_value_d_p2(): void {
        $result = app(FormatterServiceInterface::class)->floatValue('12345', 2);

        self::assertSame('123.45', $result);
    }

    /**
     * @throws ExceptionInterface
     */
    public function test_int_value_d_p3(): void {
        $result = app(FormatterServiceInterface::class)->intValue('12.345', 3);

        self::assertSame('12345', $result);
    }

    /**
     * @throws ExceptionInterface
     */
    public function test_int_value_d_p2(): void {
        $result = app(FormatterServiceInterface::class)->intValue('123.45', 2);

        self::assertSame('12345', $result);
    }
}
