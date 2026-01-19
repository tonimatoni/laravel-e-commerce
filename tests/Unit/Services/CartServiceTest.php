<?php

namespace Tests\Unit\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
    }

    public function test_add_item_creates_new_cart_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cartItem = $this->cartService->addItem($user, [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals(2, $cartItem->quantity);
    }

    public function test_add_item_increments_quantity_if_already_in_cart(): void
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

        $cartItem = $this->cartService->addItem($user, [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertEquals(5, $cartItem->quantity);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_add_item_throws_exception_when_product_out_of_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product is out of stock');

        $this->cartService->addItem($user, [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_add_item_adjusts_quantity_to_max_available_when_exceeds_stock(): void
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

        $cartItem = $this->cartService->addItem($user, [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertEquals(5, $cartItem->quantity);
    }

    public function test_get_subtotal_calculates_correct_total(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['price' => 10.00, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['price' => 20.00, 'stock_quantity' => 10]);

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

        $subtotal = $this->cartService->getSubtotal($user);

        $this->assertEquals(80.00, $subtotal);
    }

    public function test_get_tax_calculates_correct_tax(): void
    {
        config(['inventory.tax_rate' => 0.1]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $tax = $this->cartService->getTax($user);

        $this->assertEquals(10.00, $tax);
    }

    public function test_get_tax_returns_zero_when_tax_rate_is_zero(): void
    {
        config(['inventory.tax_rate' => 0]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $tax = $this->cartService->getTax($user);

        $this->assertEquals(0.0, $tax);
    }

    public function test_get_total_calculates_subtotal_plus_tax(): void
    {
        config(['inventory.tax_rate' => 0.1]);

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $total = $this->cartService->getTotal($user);

        $this->assertEquals(110.00, $total);
    }

    public function test_get_cart_count_returns_total_quantity(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 10]);

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

        $count = $this->cartService->getCartCount($user);

        $this->assertEquals(5, $count);
    }

    public function test_update_item_updates_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $updated = $this->cartService->updateItem($cartItem, ['quantity' => 5]);

        $this->assertEquals(5, $updated->quantity);
        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_update_item_deletes_when_quantity_is_zero(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->cartService->updateItem($cartItem, ['quantity' => 0]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_update_item_throws_exception_when_quantity_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->cartService->updateItem($cartItem, ['quantity' => 10]);
    }

    public function test_remove_item_deletes_cart_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $result = $this->cartService->removeItem($cartItem);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }
}
