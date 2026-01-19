<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $cartData = [];
        
        if ($request->user()) {
            // Load cart items for cross-device synchronization (Story 3.3)
            // Count is calculated efficiently using sum() for badge display (Story 4.5)
            $cartItems = $request->user()
                ->cartItems()
                ->with('product')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'price' => $item->product->price,
                            'stock_quantity' => $item->product->stock_quantity,
                        ],
                    ];
                });
            
            $cartData = [
                'items' => $cartItems,
                'count' => $request->user()->cartItems()->sum('quantity'),
            ];
        } else {
            $cartData = [
                'items' => [],
                'count' => 0,
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'cart' => $cartData,
        ];
    }
}
