<?php

namespace ArsamMe\Wallet\Test\Infra\Factories;

use ArsamMe\Wallet\Test\Infra\Models\UserFloat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserFloat>
 */
final class UserFloatFactory extends Factory {
    protected $model = UserFloat::class;

    public function definition(): array {
        return [
            'name' => fake()
                ->name,
            'email' => fake()
                ->unique()
                ->safeEmail,
        ];
    }
}
