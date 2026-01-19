<?php

namespace App\Jobs;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public int $userId
    ) {}

    public function handle(CartService $cartService): void
    {
        $order = Order::findOrFail($this->orderId);
        
        if ($order->status !== 'processing') {
            Log::warning("Order {$this->orderId} is not in processing status");
            return;
        }

        DB::transaction(function () use ($order, $cartService) {
            $user = User::findOrFail($this->userId);
            $cartItems = $user->cartItems()->with('product')->get();

            if ($cartItems->isEmpty()) {
                $order->update(['status' => 'failed']);
                throw new \Exception('Cart is empty');
            }

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;

                if ($cartItem->quantity > $product->stock_quantity) {
                    $order->update(['status' => 'failed']);
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
            $order->update(['status' => 'completed']);
        });
    }

    public function failed(\Throwable $exception): void
    {
        $order = Order::find($this->orderId);
        if ($order) {
            $order->update(['status' => 'failed']);
        }
        Log::error("Order processing failed for order {$this->orderId}: " . $exception->getMessage());
    }
}
