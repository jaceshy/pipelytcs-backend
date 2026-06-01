<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $periodDays = 30;

        $maxDate = DB::table('sales_data')->max('tanggal');
        $referenceDate = $maxDate ? Carbon::parse($maxDate) : now();

        $startDate = $referenceDate->copy()->subDays($periodDays - 1)->toDateString();
        $endDate = $referenceDate->toDateString();

        $previousEndDate = Carbon::parse($startDate)->subDay()->toDateString();
        $previousStartDate = Carbon::parse($previousEndDate)->subDays($periodDays - 1)->toDateString();

        $currentQuery = DB::table('sales_data')
            ->whereBetween('tanggal', [$startDate, $endDate]);

        $previousQuery = DB::table('sales_data')
            ->whereBetween('tanggal', [$previousStartDate, $previousEndDate]);

        $totalRevenue = (float) (clone $currentQuery)->sum('revenue');
        $totalUnitsSold = (int) (clone $currentQuery)->sum('units_sold');
        $totalOrders = (int) (clone $currentQuery)->count();

        $previousRevenue = (float) (clone $previousQuery)->sum('revenue');
        $previousUnitsSold = (int) (clone $previousQuery)->sum('units_sold');
        $previousOrders = (int) (clone $previousQuery)->count();

        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $previousAverageOrderValue = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;

        $salesTrend = DB::table('sales_data')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(
                'tanggal',
                DB::raw('SUM(revenue) as total_revenue'),
                DB::raw('SUM(units_sold) as total_units_sold')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $platformPerformance = DB::table('sales_data')
            ->join('platforms', 'sales_data.platform_id', '=', 'platforms.id')
            ->whereBetween('sales_data.tanggal', [$startDate, $endDate])
            ->select(
                'platforms.id',
                'platforms.nama as nama_platform',
                DB::raw('SUM(sales_data.revenue) as total_revenue'),
                DB::raw('SUM(sales_data.units_sold) as total_units_sold')
            )
            ->groupBy('platforms.id', 'platforms.nama')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($platform) use ($totalRevenue) {
                $platform->percentage = $totalRevenue > 0
                    ? round(($platform->total_revenue / $totalRevenue) * 100, 1)
                    : 0;

                return $platform;
            });

        $topProducts = DB::table('sales_data')
            ->join('products', 'sales_data.product_id', '=', 'products.id')
            ->join('platforms', 'sales_data.platform_id', '=', 'platforms.id')
            ->whereBetween('sales_data.tanggal', [$startDate, $endDate])
            ->select(
                'products.id',
                'products.nama_produk',
                'products.sku',
                'products.trend',
                'platforms.nama as nama_platform',
                DB::raw('SUM(sales_data.revenue) as total_revenue'),
                DB::raw('SUM(sales_data.units_sold) as total_units_sold')
            )
            ->groupBy(
                'products.id',
                'products.nama_produk',
                'products.sku',
                'products.trend',
                'platforms.nama'
            )
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'summary' => [
                'total_sales' => $totalRevenue,
                'sales_growth' => $this->calculateChange($totalRevenue, $previousRevenue),
                'units_sold' => $totalUnitsSold,
                'units_sold_growth' => $this->calculateChange($totalUnitsSold, $previousUnitsSold),
                'average_order_value' => $averageOrderValue,
                'average_order_value_growth' => $this->calculateChange($averageOrderValue, $previousAverageOrderValue),
                'total_orders' => $totalOrders,
                'total_products' => DB::table('products')->count(),
                'total_platforms' => DB::table('platforms')->count(),
            ],
            'sales_trend' => $salesTrend,
            'platform_performance' => $platformPerformance,
            'top_products' => $topProducts,
        ]);
    }

    private function calculateChange($current, $previous)
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}