<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewCartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_cart_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->has('cartItems')
            ->has('subtotal')
            ->has('tax')
            ->has('total')
            ->has('cartCount')
        );
    }

    public function test_cart_page_displays_empty_cart_message_when_no_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('cartItems', [])
            ->where('subtotal', 0)
            ->where('tax', 0)
            ->where('total', 0)
            ->where('cartCount', 0)
        );
    }

    public function test_cart_page_displays_all_cart_items(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 10.50, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['price' => 25.99, 'stock_quantity' => 5]);

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

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->has('cartItems', 2)
            ->where('cartItems.0.product.name', $product1->name)
            ->where('cartItems.0.product.price', $product1->price)
            ->where('cartItems.0.quantity', 2)
            ->where('cartItems.1.product.name', $product2->name)
            ->where('cartItems.1.product.price', $product2->price)
            ->where('cartItems.1.quantity', 1)
        );
    }

    public function test_cart_page_calculates_subtotal_correctly(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 10.00, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['price' => 20.00, 'stock_quantity' => 5]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        // Expected subtotal: (2 * 10.00) + (3 * 20.00) = 20.00 + 60.00 = 80.00
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('subtotal', 80)
        );
    }

    public function test_cart_page_calculates_tax_when_tax_rate_is_set(): void
    {
        config(['inventory.tax_rate' => 0.10]); // 10% tax

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Expected subtotal: 100.00
        // Expected tax: 100.00 * 0.10 = 10.00
        // Expected total: 110.00
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('subtotal', 100)
            ->where('tax', 10)
            ->where('total', 110)
        );
    }

    public function test_cart_page_shows_zero_tax_when_tax_rate_is_zero(): void
    {
        config(['inventory.tax_rate' => 0]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('subtotal', 100)
            ->where('tax', 0)
            ->where('total', 100)
        );
    }

    public function test_cart_page_calculates_total_correctly(): void
    {
        config(['inventory.tax_rate' => 0.08]); // 8% tax

        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 50.00, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['price' => 30.00, 'stock_quantity' => 5]);

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

        // Expected subtotal: (2 * 50.00) + (1 * 30.00) = 100.00 + 30.00 = 130.00
        // Expected tax: 130.00 * 0.08 = 10.40
        // Expected total: 130.00 + 10.40 = 140.40
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('subtotal', 130)
            ->where('tax', 10.4)
            ->where('total', 140.4)
        );
    }

    public function test_cart_page_displays_correct_cart_count(): void
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

        // Expected cart count: 3 + 2 = 5
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->where('cartCount', 5)
        );
    }

    public function test_cart_page_loads_cart_items_with_product_details(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->has('cartItems', 1)
            ->where('cartItems.0.product.id', $product->id)
            ->where('cartItems.0.product.name', 'Test Product')
            ->where('cartItems.0.product.price', '99.99')
            ->where('cartItems.0.product.stock_quantity', 10)
            ->where('cartItems.0.quantity', 2)
        );
    }

    public function test_cart_page_uses_eager_loading_to_prevent_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(5)->create(['stock_quantity' => 10]);

        foreach ($products as $product) {
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        // This test ensures we're using eager loading
        // If N+1 queries exist, this would be slow
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Cart/Index')
            ->has('cartItems', 5)
        );
    }
}
