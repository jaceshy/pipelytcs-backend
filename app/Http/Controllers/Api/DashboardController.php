<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = DB::table('users')->count();
        $totalProducts = DB::table('products')->count();
        $totalPlatforms = DB::table('platforms')->count();
        $totalSalesData = DB::table('sales_data')->count();

        $totalRevenue = DB::table('sales_data')->sum('revenue');
        $totalUnitsSold = DB::table('sales_data')->sum('units_sold');

        $topProducts = DB::table('sales_data')
            ->join('products', 'sales_data.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.nama_produk',
                DB::raw('SUM(sales_data.revenue) as total_revenue'),
                DB::raw('SUM(sales_data.units_sold) as total_units_sold')
            )
            ->groupBy('products.id', 'products.nama_produk')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $platformPerformance = DB::table('sales_data')
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
            'message' => 'Dashboard data retrieved successfully',
            'summary' => [
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'total_platforms' => $totalPlatforms,
                'total_sales_data' => $totalSalesData,
                'total_revenue' => $totalRevenue,
                'total_units_sold' => $totalUnitsSold,
            ],
            'top_products' => $topProducts,
            'platform_performance' => $platformPerformance,
        ]);
    }
}