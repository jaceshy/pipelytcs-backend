<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //get produk
    public function index()
    {
        $products = Product::with(['salesData'])->get();
        return response()->json(['message' => 'Products retrieved', 'data' => $products]);
    }

    //get product by id
    public function show($id)
    {
        $product = Product::with(['salesData'])->findOrFail($id);
        return response()->json(['message' => 'Product retrieved', 'data' => $product]);
    }

    //post product
    public function store(Request $request)
    {
    $data = $request->only([
        'user_id','nama_produk','sku','category','price','units_sold','trend'
    ]);

    $product = Product::create($data);

    //otomatis buat sales data awal saat produk dibuat
    $product->salesData()->create([
        'platform_id' => 1, //default
        'tanggal' => now(),
        'units_sold' => $data['units_sold'],
        'revenue' => $data['price'] * $data['units_sold']
    ]);

    // ptional: sync platform jika pakai platform_ids
    if ($request->has('platform_ids')) {
        $product->platforms()->sync($request->input('platform_ids'));
    }

    return response()->json([
        'message' => 'Product created with sales data',
        'data' => $product
    ], 201);
    }

    // PUT/PATCH /api/products/{id}
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->only([
            'nama_produk','sku','category','price','units_sold','trend'
        ]);

        $product->update($data);

        return response()->json(['message' => 'Product updated', 'data' => $product]);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}