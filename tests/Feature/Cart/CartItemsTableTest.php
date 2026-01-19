<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartItemsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_items_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('cart_items'));
    }

    public function test_cart_items_table_has_required_columns(): void
    {
        $columns = Schema::getColumnListing('cart_items');
        
        $this->assertContains('id', $columns);
        $this->assertContains('user_id', $columns);
        $this->assertContains('product_id', $columns);
        $this->assertContains('quantity', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('updated_at', $columns);
    }

    public function test_cart_items_table_has_foreign_key_to_users(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_items_table_has_foreign_key_to_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_items_table_has_unique_constraint_on_user_and_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_items_cascade_delete_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_cart_items_cascade_delete_when_product_deleted(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $product->forceDelete();

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_cart_items_quantity_defaults_to_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $cartItem->refresh();

        $this->assertEquals(1, $cartItem->quantity);
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 1,
        ]);
    }
}
