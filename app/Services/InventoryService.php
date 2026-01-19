<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        protected Product $product
    ) {}

    public function checkStock(Product $product, int $quantity): bool
    {
        return $product->stock_quantity >= $quantity;
    }

    public function decrementStock(Product $product, int $quantity): Product
    {
        return DB::transaction(function () use ($product, $quantity) {
            $product->lockForUpdate();

            if ($product->stock_quantity < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $product->decrement('stock_quantity', $quantity);

            return $product->fresh();
        });
    }

    public function incrementStock(Product $product, int $quantity): Product
    {
        return DB::transaction(function () use ($product, $quantity) {
            $product->increment('stock_quantity', $quantity);

            return $product->fresh();
        });
    }

    public function getLowStockProducts(?int $threshold = null): Collection
    {
        $threshold = $threshold ?? config('inventory.low_stock_threshold', 5);

        return $this->product->lowStock($threshold)->get();
    }
}
