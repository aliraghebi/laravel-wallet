<?php

namespace ArsamMe\Wallet\Test\Infra\Factories;

use ArsamMe\Wallet\Test\Infra\Models\ItemDiscountTax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemDiscountTax>
 */
final class ItemDiscountTaxFactory extends Factory {
    protected $model = ItemDiscountTax::class;

    public function definition(): array {
        return [
            'name' => fake()
                ->domainName,
            'price' => 250,
            'quantity' => 90,
        ];
    }
}
