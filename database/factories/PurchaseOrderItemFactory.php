<?php

namespace Database\Factories;

use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => \App\Models\PurchaseOrder::factory(),
            'product_id' => \App\Models\Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'unit_cost' => fake()->randomFloat(2, 10, 1000),
            'subtotal' => fn (array $attrs) => $attrs['quantity'] * $attrs['unit_cost'],
            'quantity_received' => 0,
        ];
    }
}
