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

    public function getSubtotal(User $user): float
    {
        return $user->cartItems()
            ->with('product')
            ->get()
            ->sum(function ($item) {
                return $item->quantity * $item->product->price;
            });
    }

    public function getTax(User $user): float
    {
        $taxRate = config('inventory.tax_rate', 0);

        if ($taxRate <= 0) {
            return 0.0;
        }

        return $this->getSubtotal($user) * $taxRate;
    }

    public function getTotal(User $user): float
    {
        return $this->getSubtotal($user) + $this->getTax($user);
    }

    public function getCartCount(User $user): int
    {
        return $user->cartItems()->sum('quantity');
    }

    public function updateItem(CartItem $cartItem, array $data): CartItem
    {
        return DB::transaction(function () use ($cartItem, $data) {
            $cartItem->load('product');
            $product = $cartItem->product;

            if ($data['quantity'] > $product->stock_quantity) {
                throw new \Exception('Insufficient stock. Available: ' . $product->stock_quantity);
            }

            if ($data['quantity'] <= 0) {
                $cartItem->delete();
                return $cartItem;
            }

            $cartItem->quantity = $data['quantity'];
            $cartItem->save();

            return $cartItem->load('product');
        });
    }

    public function removeItem(CartItem $cartItem): bool
    {
        return DB::transaction(function () use ($cartItem) {
            return $cartItem->delete();
        });
    }
}
