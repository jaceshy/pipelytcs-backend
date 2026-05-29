<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SaleData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'buyer_email' => 'required|email|max:100',
            'sku' => 'required|string|exists:products,sku',
            'platform_id' => 'required|exists:platforms,id',
            'tanggal' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'order_value' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::where('sku', $validated['sku'])->firstOrFail();

            $isAvailable = $product->platforms()
                ->where('platforms.id', $validated['platform_id'])
                ->exists();

            if (!$isAvailable) {
                DB::rollBack();

                return response()->json([
                    'message' => 'Product is not available on this platform',
                ], 422);
            }

            $stock = ProductStock::where('product_id', $product->id)
                ->where('platform_id', $validated['platform_id'])
                ->first();

            if (!$stock) {
                DB::rollBack();

                return response()->json([
                    'message' => 'Product stock not found on this platform',
                ], 422);
            }

            if ($stock->stock < $validated['quantity']) {
                DB::rollBack();

                return response()->json([
                    'message' => 'Insufficient stock',
                ], 422);
            }

            SaleData::create([
                'buyer_email' => $validated['buyer_email'],
                'product_id' => $product->id,
                'platform_id' => $validated['platform_id'],
                'tanggal' => $validated['tanggal'],
                'units_sold' => $validated['quantity'],
                'price' => $validated['order_value'] / $validated['quantity'],
                'revenue' => $validated['order_value'],
            ]);

            $stock->decrement('stock', $validated['quantity']);

            $totalUnitsSold = SaleData::where('product_id', $product->id)->sum('units_sold');
            $totalRevenue = SaleData::where('product_id', $product->id)->sum('revenue');

            $product->update([
                'units_sold' => $totalUnitsSold,
                'revenue' => $totalRevenue,
                'trend' => $this->calculateTrend($totalUnitsSold),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Purchase added successfully',
                'data' => $product->fresh(['salesData', 'platforms', 'stocks']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to add purchase',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateTrend($unitsSold)
    {
        if ($unitsSold >= 100) {
            return 'Fast Moving';
        }

        if ($unitsSold <= 30) {
            return 'Slow Moving';
        }

        return 'Normal';
    }
}