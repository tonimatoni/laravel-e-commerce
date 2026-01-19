<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        protected CartItem $cartItem,
        protected Product $product
    ) {}

    public function generateDailyReport(?Carbon $date = null): array
    {
        $date = $date ?? today();

        $cartItems = $this->cartItem
            ->whereDate('created_at', $date)
            ->with('product')
            ->get();

        $grouped = $cartItems->groupBy('product_id');

        $products = $grouped->map(function (Collection $items, int $productId) {
            $product = $items->first()->product;
            $totalUnitsSold = $items->sum('quantity');
            $revenue = $items->sum(function ($item) {
                return $item->quantity * $item->product->price;
            });

            return [
                'product_id' => $productId,
                'product_name' => $product->name,
                'total_units_sold' => $totalUnitsSold,
                'revenue' => round($revenue, 2),
            ];
        })->values();

        return [
            'date' => $date->format('Y-m-d'),
            'total_items_sold' => $cartItems->sum('quantity'),
            'total_revenue' => round($cartItems->sum(function ($item) {
                return $item->quantity * $item->product->price;
            }), 2),
            'products' => $products->toArray(),
        ];
    }
}
