<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveFromCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_remove_item_from_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->delete("/cart/{$cartItem->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_cart_totals_recalculate_after_removal(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create([
            'price' => 10.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        $product2 = Product::factory()->create([
            'price' => 20.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem1 = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $cartItem2 = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)->delete("/cart/{$cartItem1->id}");

        $remainingItems = $user->cartItems()->with('product')->get();
        $expectedTotal = 3 * 20.00;
        $actualTotal = $remainingItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        $this->assertEqualsWithDelta($expectedTotal, $actualTotal, 0.01);
    }

    public function test_cart_count_updates_after_removal(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        $product2 = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem1 = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $cartItem2 = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)->delete("/cart/{$cartItem1->id}");

        $cartCount = $user->cartItems()->sum('quantity');
        $this->assertEquals(3, $cartCount);
    }

    public function test_cannot_remove_other_users_cart_items(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user2)->delete("/cart/{$cartItem->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_remove_from_cart_requires_authentication(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->delete("/cart/{$cartItem->id}");

        $response->assertRedirect('/login');
    }

    public function test_success_message_displayed_after_removal(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->delete("/cart/{$cartItem->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Item removed from cart successfully');
    }

    public function test_removing_last_item_shows_empty_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->delete("/cart/{$cartItem->id}");

        $cartCount = $user->cartItems()->count();
        $this->assertEquals(0, $cartCount);
    }
}
