<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['platforms', 'stocks'])->get();

        return response()->json([
            'message' => 'Products retrieved',
            'data' => $products,
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['platforms', 'stocks'])->findOrFail($id);

        return response()->json([
            'message' => 'Product retrieved',
            'data' => $product,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:products,sku',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock_awal' => 'required|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'platform_ids' => 'required|array|min:1',
            'platform_ids.*' => 'exists:platforms,id',
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

            $platformIds = $validated['platform_ids'];
            $product->platforms()->sync($platformIds);

            foreach ($platformIds as $platformId) {
                DB::table('product_stock')->insert([
                    'product_id' => $product->id,
                    'platform_id' => $platformId,
                    'stock' => $validated['stock_awal'],
                    'minimum_stock' => $validated['minimum_stock'] ?? 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product->fresh(['platforms', 'stocks']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'nama_produk' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'stock_awal' => 'required|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'platform_ids' => 'required|array|min:1',
            'platform_ids.*' => 'exists:platforms,id',
        ]);

        DB::beginTransaction();

        try {
            $product->update([
                'nama_produk' => $validated['nama_produk'],
                'sku' => $validated['sku'],
                'category' => $validated['category'],
                'price' => $validated['price'],
            ]);

            $platformIds = $validated['platform_ids'];
            $product->platforms()->sync($platformIds);

            DB::table('product_stock')
                ->where('product_id', $product->id)
                ->whereNotIn('platform_id', $platformIds)
                ->delete();

            foreach ($platformIds as $platformId) {
                DB::table('product_stock')->updateOrInsert(
                    [
                        'product_id' => $product->id,
                        'platform_id' => $platformId,
                    ],
                    [
                        'stock' => $validated['stock_awal'],
                        'minimum_stock' => $validated['minimum_stock'] ?? 5,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $product->fresh(['platforms', 'stocks']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            DB::table('product_stock')->where('product_id', $product->id)->delete();
            $product->platforms()->detach();
            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product deleted',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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
            'data' => $lowStockProducts,
        ]);
    }
}