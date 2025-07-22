<?php

namespace AliRaghebi\Wallet\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class NumberCast implements CastsAttributes {

    public function __construct()
    {
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): string {

    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed {}
}
