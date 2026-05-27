<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // GET all products with platforms and stock info
    public function index()
    {
        $products = Product::with(['platforms', 'stocks'])->get();
        return response()->json([
            'message' => 'Products retrieved',
            'data' => $products
        ]);
    }

    // GET product by ID
    public function show($id)
    {
        $product = Product::with(['platforms', 'stocks'])->findOrFail($id);
        return response()->json([
            'message' => 'Product retrieved',
            'data' => $product
        ]);
    }

    // POST add new product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:products,sku',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock_awal' => 'required|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'user_id' => auth()->id() ?? 1,
                'nama_produk' => $validated['nama_produk'],
                'sku' => $validated['sku'],
                'category' => $validated['category'],
                'price' => $validated['price'],
                'units_sold' => 0,
                'revenue' => 0,
                'trend' => 'Normal',
            ]);

            // assign to all platforms
            $platform_ids = DB::table('platforms')->pluck('id')->toArray();
            $product->platforms()->sync($platform_ids);

            // create stock for each platform
            foreach ($platform_ids as $platform_id) {
                DB::table('product_stock')->insert([
                    'product_id' => $product->id,
                    'platform_id' => $platform_id,
                    'stock' => $validated['stock_awal'],
                    'minimum_stock' => $validated['minimum_stock'] ?? 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product->load('platforms')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PUT update product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'nama_produk' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated',
            'data' => $product
        ]);
    }

    // DELETE product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }

    // GET products with stock <= minimum_stock
    public function lowStock()
    {
        $lowStockProducts = DB::table('product_stock')
            ->join('products', 'product_stock.product_id', '=', 'products.id')
            ->join('platforms', 'product_stock.platform_id', '=', 'platforms.id')
            ->whereColumn('product_stock.stock', '<=', 'product_stock.minimum_stock')
            ->select(
                'products.id as product_id',
                'products.nama_produk',
                'platforms.id as platform_id',
                'platforms.nama as platform_name',
                'product_stock.stock',
                'product_stock.minimum_stock'
            )
            ->get();

        return response()->json([
            'message' => 'Low stock products retrieved',
            'data' => $lowStockProducts
        ]);
    }
}