<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function createOrder(User $user, array $checkoutData): Order
    {
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            throw new \Exception('Cart is empty');
        }

        $subtotal = $this->cartService->getSubtotal($user);
        $tax = $this->cartService->getTax($user);
        $total = $this->cartService->getTotal($user);

        return Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'processing',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'shipping_name' => $checkoutData['shipping_name'],
            'shipping_email' => $checkoutData['shipping_email'],
            'shipping_phone' => $checkoutData['shipping_phone'] ?? null,
            'shipping_address' => $checkoutData['shipping_address'],
            'shipping_city' => $checkoutData['shipping_city'],
            'shipping_state' => $checkoutData['shipping_state'] ?? null,
            'shipping_postal_code' => $checkoutData['shipping_postal_code'],
            'shipping_country' => $checkoutData['shipping_country'] ?? 'US',
            'billing_name' => $checkoutData['billing_name'] ?? $checkoutData['shipping_name'],
            'billing_email' => $checkoutData['billing_email'] ?? $checkoutData['shipping_email'],
            'billing_address' => $checkoutData['billing_address'] ?? $checkoutData['shipping_address'],
            'billing_city' => $checkoutData['billing_city'] ?? $checkoutData['shipping_city'],
            'billing_state' => $checkoutData['billing_state'] ?? $checkoutData['shipping_state'] ?? null,
            'billing_postal_code' => $checkoutData['billing_postal_code'] ?? $checkoutData['shipping_postal_code'],
            'billing_country' => $checkoutData['billing_country'] ?? $checkoutData['shipping_country'] ?? 'US',
        ]);
    }

    public function processCheckout(User $user, array $checkoutData): Order
    {
        return DB::transaction(function () use ($user, $checkoutData) {
            $cartItems = $user->cartItems()->with('product')->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception('Cart is empty');
            }

            $subtotal = $this->cartService->getSubtotal($user);
            $tax = $this->cartService->getTax($user);
            $total = $this->cartService->getTotal($user);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'shipping_name' => $checkoutData['shipping_name'],
                'shipping_email' => $checkoutData['shipping_email'],
                'shipping_phone' => $checkoutData['shipping_phone'] ?? null,
                'shipping_address' => $checkoutData['shipping_address'],
                'shipping_city' => $checkoutData['shipping_city'],
                'shipping_state' => $checkoutData['shipping_state'] ?? null,
                'shipping_postal_code' => $checkoutData['shipping_postal_code'],
                'shipping_country' => $checkoutData['shipping_country'] ?? 'US',
                'billing_name' => $checkoutData['billing_name'] ?? $checkoutData['shipping_name'],
                'billing_email' => $checkoutData['billing_email'] ?? $checkoutData['shipping_email'],
                'billing_address' => $checkoutData['billing_address'] ?? $checkoutData['shipping_address'],
                'billing_city' => $checkoutData['billing_city'] ?? $checkoutData['shipping_city'],
                'billing_state' => $checkoutData['billing_state'] ?? $checkoutData['shipping_state'] ?? null,
                'billing_postal_code' => $checkoutData['billing_postal_code'] ?? $checkoutData['shipping_postal_code'],
                'billing_country' => $checkoutData['billing_country'] ?? $checkoutData['shipping_country'] ?? 'US',
            ]);

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                if ($cartItem->quantity > $product->stock_quantity) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                ]);

                $product->decrement('stock_quantity', $cartItem->quantity);
            }

            $user->cartItems()->delete();

            return $order->load('orderItems.product');
        });
    }
}
