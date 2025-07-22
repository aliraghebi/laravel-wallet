<?php

namespace AliRaghebi\Wallet\Test\Factories;

use AliRaghebi\Wallet\Test\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory {
    protected $model = User::class;

    public function definition(): array {
        return [
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
        ];
    }
}
