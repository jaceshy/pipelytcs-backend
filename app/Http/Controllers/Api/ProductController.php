<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['platforms', 'salesData'])->get();

        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => $products
        ]);
    }
}