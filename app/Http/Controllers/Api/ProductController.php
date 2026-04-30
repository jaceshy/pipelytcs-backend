<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('platforms')->get();

        return response()->json([
            'message' => 'Product data fetched successfully',
            'data' => $products
        ]);
    }
}