<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Cart/Index', [
            'cartItems' => [],
            'cartTotal' => 0,
            'cartCount' => 0,
        ]);
    }
}
