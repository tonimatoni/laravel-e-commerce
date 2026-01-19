<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_page_requires_authentication(): void
    {
        $response = $this->get('/products');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_products_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Products/Index'));
    }

    public function test_products_are_displayed_in_catalog(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create([
            'name' => 'Product 1',
            'price' => 29.99,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        $product2 = Product::factory()->create([
            'name' => 'Product 2',
            'price' => 49.99,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('products.data', function ($products) {
                $names = collect($products)->pluck('name')->toArray();
                return in_array('Product 1', $names) && in_array('Product 2', $names);
            })
        );
    }

    public function test_only_active_products_are_displayed(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'name' => 'Active Product',
            'is_active' => true,
            'stock_quantity' => 10,
        ]);
        Product::factory()->create([
            'name' => 'Inactive Product',
            'is_active' => false,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Active Product')
        );
    }

    public function test_only_in_stock_products_are_displayed(): void
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

    public function test_products_are_sorted_by_latest(): void
    {
        $user = User::factory()->create();
        $oldProduct = Product::factory()->create([
            'name' => 'Old Product',
            'created_at' => now()->subDays(2),
            'is_active' => true,
            'stock_quantity' => 10,
        ]);
        $newProduct = Product::factory()->create([
            'name' => 'New Product',
            'created_at' => now(),
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->where('products.data.0.name', 'New Product')
            ->where('products.data.1.name', 'Old Product')
        );
    }

    public function test_products_are_paginated(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(15)->create([
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 12)
            ->has('products.links')
        );
    }

    public function test_product_information_is_included(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 29.99,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data.0.id')
            ->has('products.data.0.name')
            ->has('products.data.0.price')
            ->has('products.data.0.stock_quantity')
            ->has('products.data.0.description')
        );
    }

    public function test_empty_catalog_shows_no_products_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 0)
        );
    }

    public function test_pagination_links_are_available(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(25)->create([
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertInertia(fn ($page) => $page
            ->has('products.links')
            ->where('products.current_page', 1)
            ->where('products.per_page', 12)
        );
    }
}
