<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_quantity_displayed_for_in_stock_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'In Stock Product',
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->where('products.data.0.stock_quantity', 10)
        );
    }

    public function test_low_stock_badge_displayed_when_stock_below_five(): void
    {
        $user = User::factory()->create();
        $lowStockProduct = Product::factory()->create([
            'name' => 'Low Stock Product',
            'stock_quantity' => 3,
            'is_active' => true,
        ]);
        $normalStockProduct = Product::factory()->create([
            'name' => 'Normal Stock Product',
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('products.data', function ($products) use ($lowStockProduct) {
                $lowStock = collect($products)->firstWhere('id', $lowStockProduct->id);
                return $lowStock && $lowStock['stock_quantity'] === 3;
            })
        );
    }

    public function test_out_of_stock_message_displayed_when_stock_is_zero(): void
    {
        $user = User::factory()->create();
        $outOfStockProduct = Product::factory()->create([
            'name' => 'Out of Stock Product',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 0)
        );
    }

    public function test_out_of_stock_products_not_displayed_in_catalog(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'name' => 'In Stock Product',
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Out of Stock Product',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'In Stock Product')
        );
    }

    public function test_stock_status_badges_displayed_correctly(): void
    {
        $user = User::factory()->create();
        $inStockProduct = Product::factory()->create([
            'name' => 'In Stock',
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        $lowStockProduct = Product::factory()->create([
            'name' => 'Low Stock',
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('products.data', function ($products) {
                $inStock = collect($products)->firstWhere('stock_quantity', 10);
                $lowStock = collect($products)->firstWhere('stock_quantity', 3);
                
                return $inStock && $lowStock
                    && $inStock['stock_quantity'] > 0
                    && $lowStock['stock_quantity'] > 0
                    && $lowStock['stock_quantity'] < 5;
            })
        );
    }

    public function test_stock_quantity_shown_for_low_stock_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Low Stock Product',
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->where('products.data.0.stock_quantity', 4)
        );
    }

    public function test_stock_quantity_shown_for_normal_stock_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Normal Stock Product',
            'stock_quantity' => 15,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->where('products.data.0.stock_quantity', 15)
        );
    }
}
