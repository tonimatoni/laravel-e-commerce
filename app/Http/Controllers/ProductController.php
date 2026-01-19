<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::active()
            ->inStock()
            ->latest()
            ->paginate(12);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }
}
