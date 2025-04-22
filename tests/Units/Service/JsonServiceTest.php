<?php

namespace ArsamMe\Wallet\Test\Units\Service;

use ArsamMe\Wallet\Internal\Service\JsonService;
use ArsamMe\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class JsonServiceTest extends TestCase {
    public function test_json_encode_success(): void {
        $jsonService = app(JsonService::class);
        self::assertNull($jsonService->encode(null));
        self::assertJson((string) $jsonService->encode([1]));
    }

    public function test_json_encode_failed(): void {
        $jsonService = app(JsonService::class);
        $array = [1];
        $array[] = &$array;

        self::assertNull($jsonService->encode($array));
    }
}
