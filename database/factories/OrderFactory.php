<?php

namespace Database\Factories;
use App\Models\Hold;
use App\Models\Product;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'hold_id' => Hold::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'total' => fake()->randomFloat(2, 10, 1000),
            'status' => 'pending',
        ];
    }
}
