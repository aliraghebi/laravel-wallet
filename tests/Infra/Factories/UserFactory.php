<?php

namespace ArsamMe\Wallet\Test\Infra\Factories;

use ArsamMe\Wallet\Test\Infra\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory {
    protected $model = User::class;

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
