<?php

namespace AliRaghebi\Wallet\Test\Unit;

use AliRaghebi\Wallet\Contracts\Services\JsonServiceInterface;
use AliRaghebi\Wallet\Services\JsonService;
use AliRaghebi\Wallet\Test\TestCase;

/**
 * @internal
 */
final class JsonServiceTest extends TestCase {
    public function test_json_encode_success(): void {
        $jsonService = app(JsonServiceInterface::class);
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
