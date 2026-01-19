<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Jobs\ProcessOrder;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\CartService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        protected CheckoutService $checkoutService,
        protected CartService $cartService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $cartItems = $user->cartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty');
        }

        $subtotal = $this->cartService->getSubtotal($user);
        $tax = $this->cartService->getTax($user);
        $total = $this->cartService->getTotal($user);

        return Inertia::render('Checkout/Index', [
            'cartItems' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                    ],
                ];
            }),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        try {
            $user = auth()->user();
            $order = $this->checkoutService->createOrder($user, $request->validated());

            ProcessOrder::dispatch($order->id, $user->id);

            return redirect()->route('checkout.processing', $order);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function processing(Order $order): Response
    {
        $this->authorize('view', $order);

        return Inertia::render('Checkout/Processing', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);
    }

    public function status(Order $order): StreamedResponse
    {
        $this->authorize('view', $order);

        return response()->stream(function () use ($order) {
            $maxAttempts = 60;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                $order->refresh();

                $data = [
                    'status' => $order->status,
                    'order_id' => $order->id,
                ];

                echo "data: " . json_encode($data) . "\n\n";
                ob_flush();
                flush();

                if ($order->status === 'completed' || $order->status === 'failed') {
                    break;
                }

                sleep(1);
                $attempt++;
            }

            if ($attempt >= $maxAttempts) {
                echo "data: " . json_encode(['status' => 'timeout', 'order_id' => $order->id]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function confirmation(Order $order): Response|RedirectResponse
    {
        $this->authorize('view', $order);

        if ($order->status === 'processing') {
            return redirect()->route('checkout.processing', $order);
        }

        if ($order->status === 'failed') {
            return redirect()->route('checkout.index')
                ->with('error', 'Order processing failed. Please try again.');
        }

        $order->load('orderItems.product');

        if ($order->orderItems->isEmpty()) {
            return redirect()->route('checkout.index')
                ->with('error', 'Order not found or incomplete.');
        }

        return Inertia::render('Checkout/Confirmation', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'subtotal' => (float) $order->subtotal,
                'tax' => (float) $order->tax,
                'total' => (float) $order->total,
                'shipping_name' => $order->shipping_name,
                'shipping_email' => $order->shipping_email,
                'shipping_address' => $order->shipping_address,
                'shipping_city' => $order->shipping_city,
                'shipping_state' => $order->shipping_state,
                'shipping_postal_code' => $order->shipping_postal_code,
                'shipping_country' => $order->shipping_country,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'order_items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }),
            ],
        ]);
    }

    public function invoice(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('orderItems.product', 'user');

        $html = view('invoices.order', [
            'order' => $order,
        ])->render();

        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
