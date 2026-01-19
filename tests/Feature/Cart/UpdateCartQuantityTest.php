<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCartQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_cart_item_quantity(): void
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

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_update_quantity_higher_than_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 10,
        ]);

        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_quantity_must_be_positive_integer(): void
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

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['quantity']);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => -1,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_cannot_update_other_users_cart_items(): void
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

        $response = $this->actingAs($user2)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_totals_recalculate_after_quantity_update(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 10.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertRedirect();

        $cartItem->refresh();
        $expectedSubtotal = 5 * 10.00;
        $this->assertEqualsWithDelta($expectedSubtotal, $cartItem->quantity * $cartItem->product->price, 0.01);
    }

    public function test_update_cart_quantity_requires_authentication(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = CartItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_quantity_cannot_exceed_max_value(): void
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

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 1000,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_success_message_displayed_after_update(): void
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

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cart updated successfully');
    }
}
