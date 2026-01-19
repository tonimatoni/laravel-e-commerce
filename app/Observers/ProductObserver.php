<?php

namespace App\Observers;

use App\Jobs\NotifyLowStock;
use App\Models\Product;

class ProductObserver
{
    public function updated(Product $product): void
    {
        if (!$product->wasChanged('stock_quantity')) {
            return;
        }

        $threshold = config('inventory.low_stock_threshold', 5);
        $currentStock = $product->stock_quantity;
        $previousStock = $product->getOriginal('stock_quantity');

        if ($currentStock > 0 
            && $currentStock <= $threshold 
            && $previousStock >= $threshold
        ) {
            NotifyLowStock::dispatch($product);
        }
    }
}
