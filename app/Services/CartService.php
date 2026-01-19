<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected CartItem $cartItem,
        protected Product $product
    ) {}

    public function addItem(User $user, array $data): CartItem
    {
        return DB::transaction(function () use ($user, $data) {
            $product = $this->product->findOrFail($data['product_id']);

            if ($product->stock_quantity === 0) {
                throw new \Exception('Product is out of stock');
            }

            $cartItem = $this->cartItem->firstOrNew([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            $newQuantity = $cartItem->exists 
                ? $cartItem->quantity + $data['quantity']
                : $data['quantity'];

            if ($newQuantity > $product->stock_quantity) {
                if (!$cartItem->exists) {
                    throw new \Exception('Insufficient stock. Available: ' . $product->stock_quantity);
                }
                $newQuantity = $product->stock_quantity;
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();

            return $cartItem->load('product');
        });
    }

    public function getTotal(User $user): float
    {
        return $user->cartItems()
            ->with('product')
            ->get()
            ->sum(function ($item) {
                return $item->quantity * $item->product->price;
            });
    }

    public function getCartCount(User $user): int
    {
        return $user->cartItems()->sum('quantity');
    }
}
