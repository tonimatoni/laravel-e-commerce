<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_returns_only_active_products(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::active()->get();

        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->every(fn($product) => $product->is_active === true));
    }

    public function test_in_stock_scope_returns_only_products_with_stock(): void
    {
        Product::factory()->create(['stock_quantity' => 10]);
        Product::factory()->create(['stock_quantity' => 5]);
        Product::factory()->create(['stock_quantity' => 0]);

        $inStockProducts = Product::inStock()->get();

        $this->assertCount(2, $inStockProducts);
        $this->assertTrue($inStockProducts->every(fn($product) => $product->stock_quantity > 0));
    }

    public function test_low_stock_scope_returns_products_below_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        Product::factory()->create(['stock_quantity' => 3]);
        Product::factory()->create(['stock_quantity' => 4]);
        Product::factory()->create(['stock_quantity' => 5]);
        Product::factory()->create(['stock_quantity' => 10]);

        $lowStockProducts = Product::lowStock()->get();

        $this->assertCount(3, $lowStockProducts);
        $this->assertTrue($lowStockProducts->every(fn($product) => $product->stock_quantity <= 5 && $product->stock_quantity > 0));
    }

    public function test_low_stock_scope_uses_custom_threshold(): void
    {
        Product::factory()->create(['stock_quantity' => 3]);
        Product::factory()->create(['stock_quantity' => 7]);
        Product::factory()->create(['stock_quantity' => 10]);

        $lowStockProducts = Product::lowStock(5)->get();

        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals(3, $lowStockProducts->first()->stock_quantity);
    }

    public function test_low_stock_scope_excludes_out_of_stock(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        Product::factory()->create(['stock_quantity' => 0]);
        Product::factory()->create(['stock_quantity' => 3]);
        Product::factory()->create(['stock_quantity' => 10]);

        $lowStockProducts = Product::lowStock()->get();

        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals(3, $lowStockProducts->first()->stock_quantity);
    }

    public function test_out_of_stock_scope_returns_only_zero_stock_products(): void
    {
        Product::factory()->create(['stock_quantity' => 0]);
        Product::factory()->create(['stock_quantity' => 0]);
        Product::factory()->create(['stock_quantity' => 5]);

        $outOfStockProducts = Product::outOfStock()->get();

        $this->assertCount(2, $outOfStockProducts);
        $this->assertTrue($outOfStockProducts->every(fn($product) => $product->stock_quantity === 0));
    }

    public function test_can_chain_scopes(): void
    {
        Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 10,
        ]);
        Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 0,
        ]);
        Product::factory()->create([
            'is_active' => false,
            'stock_quantity' => 10,
        ]);

        $products = Product::active()->inStock()->get();

        $this->assertCount(1, $products);
        $this->assertTrue($products->first()->is_active);
        $this->assertTrue($products->first()->stock_quantity > 0);
    }

    public function test_scopes_are_readable_and_maintainable(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 3,
        ]);

        $result = Product::active()
            ->lowStock()
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals($product->id, $result->id);
    }
}
