<?php

namespace Tests\Feature\Requests;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddToCartRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_request_passes_validation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_product_id_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart', [
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_product_id_must_be_integer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => 'not-an-integer',
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_product_id_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_product_must_be_active(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_quantity_is_required(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_quantity_must_be_integer(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 'not-an-integer',
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_quantity_cannot_exceed_max(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1000,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_validation_errors_are_returned_to_frontend(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart', []);

        $response->assertSessionHasErrors(['product_id', 'quantity']);
        $response->assertStatus(302);
    }
}
