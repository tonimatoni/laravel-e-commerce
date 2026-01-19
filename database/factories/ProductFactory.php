<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        $hasDiscount = fake()->boolean(30);
        $discountPercentage = null;
        $discountStartDate = null;
        $discountEndDate = null;

        if ($hasDiscount) {
            $discountPercentage = fake()->randomFloat(2, 5, 50);
            $discountStartDate = fake()->dateTimeBetween('-1 week', '+1 week');
            $discountEndDate = fake()->dateTimeBetween($discountStartDate, '+2 months');
        }

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'discount_percentage' => $discountPercentage,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'sku' => fake()->unique()->bothify('SKU-####-???'),
            'image_url' => fake()->imageUrl(),
            'is_active' => true,
        ];
    }
}
