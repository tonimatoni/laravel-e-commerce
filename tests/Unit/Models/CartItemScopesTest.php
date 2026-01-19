<?php

namespace Tests\Unit\Models;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartItemScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_user_scope_filters_by_user_id(): void
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

    public function test_with_product_scope_eager_loads_product(): void
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

        $this->assertCount(2, $queryLog, 'Should execute 2 queries: one for cart items, one for products');
    }

    public function test_can_chain_for_user_and_with_product_scopes(): void
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
        $this->assertTrue($cartItems->every(fn($item) => $item->user_id === $user1->id));
        $this->assertTrue($cartItems->every(fn($item) => $item->product !== null));

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(2, $queryLog, 'Should execute 2 queries: one for cart items, one for products');
    }

    public function test_scopes_prevent_n_plus_one_queries(): void
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

        $cartItems = CartItem::forUser($user->id)->withProduct()->get();

        foreach ($cartItems as $cartItem) {
            $productName = $cartItem->product->name;
            $this->assertNotEmpty($productName);
        }

        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(2, count($queryLog), 'Eager loading should prevent N+1 queries. Without eager loading, this would be 1 + 5 = 6 queries');
    }
}
