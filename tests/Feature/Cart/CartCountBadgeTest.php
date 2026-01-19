<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCountBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_count_is_shared_via_inertia_middleware(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.count', 3)
        );
    }

    public function test_cart_count_updates_immediately_after_adding_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Initial cart count should be 0
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 0)
        );

        // Add item to cart
        $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Cart count should update immediately
        $response = $this->actingAs($user)->get('/products');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 2)
        );
    }

    public function test_cart_count_updates_immediately_after_updating_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Initial cart count
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 2)
        );

        // Update quantity
        $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        // Cart count should update immediately
        $response = $this->actingAs($user)->get('/products');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 5)
        );
    }

    public function test_cart_count_updates_immediately_after_removing_item(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        $cartItem1 = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 3,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);

        // Initial cart count: 3 + 2 = 5
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 5)
        );

        // Remove one item
        $this->actingAs($user)->delete("/cart/{$cartItem1->id}");

        // Cart count should update immediately: 2
        $response = $this->actingAs($user)->get('/products');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 2)
        );
    }

    public function test_cart_count_is_zero_when_cart_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.count', 0)
        );
    }

    public function test_cart_count_is_available_on_all_pages(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $pages = ['/dashboard', '/products', '/cart', '/profile'];

        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);

            $response->assertStatus(200);
            $response->assertInertia(fn ($inertia) => $inertia
                ->has('cart')
                ->where('cart.count', 4)
            );
        }
    }

    public function test_cart_count_calculates_total_quantity_not_unique_items(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 3,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);

        // Cart count should be total quantity: 3 + 2 = 5, not 2 unique items
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 5)
        );
    }

    public function test_cart_count_is_zero_for_unauthenticated_users(): void
    {
        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.count', 0)
        );
    }

    public function test_cart_count_updates_after_multiple_operations(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        // Add first product
        $this->actingAs($user)->post('/cart', [
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->get('/products');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 2)
        );

        // Add second product
        $this->actingAs($user)->post('/cart', [
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->get('/products');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 5)
        );

        // Get cart item and update quantity
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $product1->id)
            ->first();

        $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->get('/products');
        $response->assertInertia(fn ($page) => $page
            ->where('cart.count', 4) // 1 + 3 = 4
        );
    }
}
