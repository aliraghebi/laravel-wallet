<?php

namespace ArsamMe\Wallet\Test\Infra\Factories;

use ArsamMe\Wallet\Test\Infra\Models\UserMulti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMulti>
 */
final class UserMultiFactory extends Factory {
    protected $model = UserMulti::class;

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
