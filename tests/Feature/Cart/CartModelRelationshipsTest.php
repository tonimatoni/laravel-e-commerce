<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_cart_items_relationship(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
        ]);

        $this->assertCount(2, $user->cartItems);
        $this->assertInstanceOf(CartItem::class, $user->cartItems->first());
    }

    public function test_cart_item_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(User::class, $cartItem->user);
        $this->assertEquals($user->id, $cartItem->user->id);
    }

    public function test_cart_item_belongs_to_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Product::class, $cartItem->product);
        $this->assertEquals($product->id, $cartItem->product->id);
    }

    public function test_can_eager_load_cart_items_with_products(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
        ]);

        DB::enableQueryLog();

        $user = User::with('cartItems.product')->find($user->id);

        $this->assertCount(2, $user->cartItems);
        $this->assertNotNull($user->cartItems->first()->product);
        $this->assertEquals('Product 1', $user->cartItems->first()->product->name);
        $this->assertEquals('Product 2', $user->cartItems->last()->product->name);

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, count($queryLog), 'Eager loading should prevent N+1 queries');
    }

    public function test_can_use_for_user_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create();

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
        ]);

        $user1CartItems = CartItem::forUser($user1->id)->get();

        $this->assertCount(1, $user1CartItems);
        $this->assertEquals($user1->id, $user1CartItems->first()->user_id);
    }

    public function test_can_use_with_product_scope(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product']);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        DB::enableQueryLog();

        $cartItems = CartItem::withProduct()->get();

        $this->assertCount(1, $cartItems);
        $this->assertNotNull($cartItems->first()->product);
        $this->assertEquals('Test Product', $cartItems->first()->product->name);

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(2, $queryLog);
    }

    public function test_can_chain_scopes(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product1->id,
        ]);

        CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product2->id,
        ]);

        CartItem::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product1->id,
        ]);

        DB::enableQueryLog();

        $cartItems = CartItem::forUser($user1->id)->withProduct()->get();

        $this->assertCount(2, $cartItems);
        $this->assertEquals($user1->id, $cartItems->first()->user_id);
        $this->assertNotNull($cartItems->first()->product);

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(2, $queryLog);
    }

    public function test_n_plus_one_prevention_with_eager_loading(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        }

        DB::enableQueryLog();

        $user = User::with('cartItems.product')->find($user->id);

        foreach ($user->cartItems as $cartItem) {
            $productName = $cartItem->product->name;
            $this->assertNotEmpty($productName);
        }

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, count($queryLog), 'Eager loading should prevent N+1 queries. Without eager loading, this would be 1 + 5 + 5 = 11 queries');
    }

    public function test_user_with_cart_count_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $products1 = Product::factory()->count(3)->create();
        $products2 = Product::factory()->count(2)->create();

        foreach ($products1 as $product) {
            CartItem::factory()->create([
                'user_id' => $user1->id,
                'product_id' => $product->id,
            ]);
        }

        foreach ($products2 as $product) {
            CartItem::factory()->create([
                'user_id' => $user2->id,
                'product_id' => $product->id,
            ]);
        }

        $users = User::withCartCount()->get();

        $user1 = $users->firstWhere('id', $user1->id);
        $user2 = $users->firstWhere('id', $user2->id);

        $this->assertEquals(3, $user1->cart_items_count);
        $this->assertEquals(2, $user2->cart_items_count);
    }

    public function test_cart_item_subtotal_accessor(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 29.99,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $cartItem->load('product');

        $expectedSubtotal = 29.99 * 3;
        $this->assertEqualsWithDelta($expectedSubtotal, $cartItem->subtotal, 0.01);
    }
}
