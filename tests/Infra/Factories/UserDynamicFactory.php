<?php

namespace ArsamMe\Wallet\Test\Infra\Factories;

use ArsamMe\Wallet\Test\Infra\Models\UserDynamic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserDynamic>
 */
final class UserDynamicFactory extends Factory {
    protected $model = UserDynamic::class;

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
