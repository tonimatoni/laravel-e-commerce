<?php

namespace Tests\Feature\Requests;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCartRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_request_passes_validation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_quantity_is_required(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", []);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_quantity_must_be_integer(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 'not-an-integer',
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_quantity_cannot_exceed_max(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 1000,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_user_can_only_update_own_cart_items(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user2)->put("/cart/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertForbidden();
    }

    public function test_validation_errors_are_returned_to_frontend(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->put("/cart/{$cartItem->id}", [
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['quantity']);
        $response->assertStatus(302);
    }
}
