<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => null,
            'name' => fake()->unique()->words(3, true),
            'brand' => fake()->company(),
            'description' => fake()->sentence(),
            'image_path' => null,
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'reorder_point' => fake()->numberBetween(5, 20),
        ];
    }
}
