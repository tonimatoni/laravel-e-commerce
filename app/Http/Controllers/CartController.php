<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('Cart/Index', [
            'cartItems' => $user->cartItems()
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
                }),
            'cartTotal' => $this->cartService->getTotal($user),
            'cartCount' => $this->cartService->getCartCount($user),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        try {
            $user = auth()->user();
            $this->cartService->addItem($user, $request->validated());

            return redirect()->route('products.index')
                ->with('success', 'Product added to cart successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
