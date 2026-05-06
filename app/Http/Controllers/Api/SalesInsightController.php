<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SalesInsightController extends Controller
{
    public function index()
    {
        $salesTrend = DB::table('sales_data')
            ->select(
                'tanggal',
                DB::raw('SUM(revenue) as total_revenue'),
                DB::raw('SUM(units_sold) as total_units_sold')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $productInsight = DB::table('sales_data')
            ->join('products', 'sales_data.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.nama_produk',
                'products.category',
                'products.trend',
                DB::raw('SUM(sales_data.revenue) as total_revenue'),
                DB::raw('SUM(sales_data.units_sold) as total_units_sold')
            )
            ->groupBy(
                'products.id',
                'products.nama_produk',
                'products.category',
                'products.trend'
            )
            ->orderByDesc('total_revenue')
            ->get();

        $platformInsight = DB::table('sales_data')
    ->join('platforms', 'sales_data.platform_id', '=', 'platforms.id')
    ->select(
        'platforms.id',
        'platforms.nama as nama_platform',
        DB::raw('SUM(sales_data.revenue) as total_revenue'),
        DB::raw('SUM(sales_data.units_sold) as total_units_sold')
    )
    ->groupBy('platforms.id', 'platforms.nama')
    ->orderByDesc('total_revenue')
    ->get();

        return response()->json([
            'message' => 'Sales insight data retrieved successfully',
            'sales_trend' => $salesTrend,
            'product_insight' => $productInsight,
            'platform_insight' => $platformInsight,
        ]);
    }
}