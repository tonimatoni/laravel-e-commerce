<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }

    public function test_check_stock_returns_true_when_sufficient_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $result = $this->inventoryService->checkStock($product, 5);

        $this->assertTrue($result);
    }

    public function test_check_stock_returns_false_when_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $result = $this->inventoryService->checkStock($product, 10);

        $this->assertFalse($result);
    }

    public function test_check_stock_returns_true_when_exact_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $result = $this->inventoryService->checkStock($product, 5);

        $this->assertTrue($result);
    }

    public function test_decrement_stock_reduces_stock_quantity(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->inventoryService->decrementStock($product, 3);

        $this->assertEquals(7, $updated->stock_quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 7,
        ]);
    }

    public function test_decrement_stock_throws_exception_when_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->decrementStock($product, 10);
    }

    public function test_decrement_stock_uses_pessimistic_locking(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->inventoryService->decrementStock($product, 3);

        $this->assertInstanceOf(Product::class, $updated);
        $this->assertEquals(7, $updated->fresh()->stock_quantity);
    }

    public function test_increment_stock_increases_stock_quantity(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->inventoryService->incrementStock($product, 5);

        $this->assertEquals(15, $updated->stock_quantity);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 15,
        ]);
    }

    public function test_increment_stock_returns_fresh_instance(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->inventoryService->incrementStock($product, 5);

        $this->assertInstanceOf(Product::class, $updated);
        $this->assertEquals(15, $updated->fresh()->stock_quantity);
    }

    public function test_get_low_stock_products_returns_products_below_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        Product::factory()->create(['stock_quantity' => 3]);
        Product::factory()->create(['stock_quantity' => 4]);
        Product::factory()->create(['stock_quantity' => 5]);
        Product::factory()->create(['stock_quantity' => 10]);

        $lowStockProducts = $this->inventoryService->getLowStockProducts();

        $this->assertCount(3, $lowStockProducts);
        $this->assertTrue($lowStockProducts->every(fn($product) => $product->stock_quantity <= 5 && $product->stock_quantity > 0));
    }

    public function test_get_low_stock_products_uses_custom_threshold(): void
    {
        Product::factory()->create(['stock_quantity' => 3]);
        Product::factory()->create(['stock_quantity' => 7]);
        Product::factory()->create(['stock_quantity' => 10]);

        $lowStockProducts = $this->inventoryService->getLowStockProducts(5);

        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals(3, $lowStockProducts->first()->stock_quantity);
    }

    public function test_get_low_stock_products_excludes_out_of_stock(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        Product::factory()->create(['stock_quantity' => 0]);
        Product::factory()->create(['stock_quantity' => 3]);
        Product::factory()->create(['stock_quantity' => 10]);

        $lowStockProducts = $this->inventoryService->getLowStockProducts();

        $this->assertCount(1, $lowStockProducts);
        $this->assertEquals(3, $lowStockProducts->first()->stock_quantity);
    }
}
