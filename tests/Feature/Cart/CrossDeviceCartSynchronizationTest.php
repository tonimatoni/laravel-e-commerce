<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossDeviceCartSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_items_are_shared_via_inertia_middleware(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.items', function ($items) {
                return count($items) === 2;
            })
        );
    }

    public function test_cart_items_load_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

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

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $response = $this->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.items', function ($items) {
                return count($items) === 2;
            })
        );
    }

    public function test_cart_items_available_on_all_pages(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product']);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $pages = ['/dashboard', '/cart', '/profile'];

        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);

            $response->assertInertia(fn ($inertia) => $inertia
                ->has('cart')
                ->where('cart.items', function ($items) {
                    return count($items) === 1;
                })
            );
        }
    }

    public function test_cart_items_include_product_details(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('cart.items.0.product')
            ->where('cart.items.0.product.name', 'Test Product')
            ->where('cart.items.0.product.price', '29.99')
            ->where('cart.items.0.product.stock_quantity', 10)
            ->where('cart.items.0.quantity', 2)
        );
    }

    public function test_cart_is_empty_for_unauthenticated_users(): void
    {
        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.items', [])
            ->where('cart.count', 0)
        );
    }

    public function test_cart_synchronizes_across_sessions(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 1,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $response = $this->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('cart.items', 1)
        );

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);

        $response = $this->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('cart.items', 2)
        );
    }

    public function test_cart_count_is_available(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('cart')
            ->where('cart.items', function ($items) {
                return count($items) === 3;
            })
        );
    }
}
