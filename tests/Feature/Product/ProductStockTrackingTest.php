<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_stock_quantity_field(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->assertNotNull($product->stock_quantity);
        $this->assertIsInt($product->stock_quantity);
    }

    public function test_stock_quantity_is_unsigned_integer(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 100,
        ]);

        $this->assertGreaterThanOrEqual(0, $product->stock_quantity);
        $this->assertIsInt($product->stock_quantity);
    }

    public function test_stock_quantity_cannot_be_negative(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => -5,
        ]);

        $this->assertEquals(0, $product->stock_quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 0,
        ]);
    }

    public function test_stock_quantity_defaults_to_zero_when_null(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => null,
        ]);

        $this->assertEquals(0, $product->stock_quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 0,
        ]);
    }

    public function test_stock_quantity_field_exists_in_database_schema(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 25,
        ]);

        $this->assertArrayHasKey('stock_quantity', $product->getAttributes());
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 25,
        ]);
    }

    public function test_stock_quantity_mutator_prevents_negative_values(): void
    {
        $product = new Product();
        $product->stock_quantity = -10;

        $this->assertEquals(0, $product->stock_quantity);
    }

    public function test_stock_quantity_can_be_zero(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 0,
        ]);

        $this->assertEquals(0, $product->stock_quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 0,
        ]);
    }

    public function test_stock_quantity_can_be_positive_integer(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 50,
        ]);

        $this->assertEquals(50, $product->stock_quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 50,
        ]);
    }
}
