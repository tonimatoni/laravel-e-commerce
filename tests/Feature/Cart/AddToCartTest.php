<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddToCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_adding_product_increments_quantity_if_already_in_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_quantity_adjusted_to_max_stock_if_exceeds(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_add_to_cart_requires_authentication(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_add_to_cart_validates_product_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_add_to_cart_validates_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_add_to_cart_requires_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_success_message_displayed_after_adding_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success', 'Product added to cart successfully');
    }
}
