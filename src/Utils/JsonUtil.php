<?php

namespace ArsamMe\Wallet\Utils;

use Throwable;

class JsonUtil {
    public static function encode(?array $data): ?string {
        try {
            return $data === null ? null : json_encode($data, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
    }
}
