<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInsightController extends Controller
{
    public function index(Request $request)
    {
        $periodDays = (int) $request->query('period_days', 30);
        $platformId = $request->query('platform_id');

        $maxDate = DB::table('sales_data')->max('tanggal');
        $referenceDate = $maxDate ? Carbon::parse($maxDate) : now();

        $startDate = $referenceDate->copy()->subDays($periodDays - 1)->toDateString();
        $endDate = $referenceDate->toDateString();

        $previousEndDate = Carbon::parse($startDate)->subDay()->toDateString();
        $previousStartDate = Carbon::parse($previousEndDate)->subDays($periodDays - 1)->toDateString();

        $baseQuery = DB::table('sales_data')
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($platformId && $platformId !== 'all') {
            $baseQuery->where('platform_id', $platformId);
        }

        $previousQuery = DB::table('sales_data')
            ->whereBetween('tanggal', [$previousStartDate, $previousEndDate]);

        if ($platformId && $platformId !== 'all') {
            $previousQuery->where('platform_id', $platformId);
        }

        $totalRevenue = (float) (clone $baseQuery)->sum('revenue');
        $totalUnitsSold = (int) (clone $baseQuery)->sum('units_sold');
        $totalOrders = (int) (clone $baseQuery)->count();
        $uniqueCustomers = (int) (clone $baseQuery)->distinct('buyer_email')->count('buyer_email');

        $previousRevenue = (float) (clone $previousQuery)->sum('revenue');
        $previousOrders = (int) (clone $previousQuery)->count();
        $previousUniqueCustomers = (int) (clone $previousQuery)->distinct('buyer_email')->count('buyer_email');

        $returningCustomers = DB::table('sales_data')
            ->select('buyer_email')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($platformId && $platformId !== 'all', function ($query) use ($platformId) {
                return $query->where('platform_id', $platformId);
            })
            ->groupBy('buyer_email')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $previousReturningCustomers = DB::table('sales_data')
            ->select('buyer_email')
            ->whereBetween('tanggal', [$previousStartDate, $previousEndDate])
            ->when($platformId && $platformId !== 'all', function ($query) use ($platformId) {
                return $query->where('platform_id', $platformId);
            })
            ->groupBy('buyer_email')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $previousAov = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;

        $customerRetentionRate = $uniqueCustomers > 0
            ? ($returningCustomers / $uniqueCustomers) * 100
            : 0;

        $previousRetentionRate = $previousUniqueCustomers > 0
            ? ($previousReturningCustomers / $previousUniqueCustomers) * 100
            : 0;

        $repeatPurchaseRate = $customerRetentionRate;

        $growthRate = $previousRevenue > 0
            ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100
            : ($totalRevenue > 0 ? 100 : 0);

        $salesTrend = (clone $baseQuery)
            ->select(
                'tanggal',
                DB::raw('SUM(revenue) as total_revenue'),
                DB::raw('SUM(units_sold) as total_units_sold')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $categoryRevenue = DB::table('sales_data')
            ->join('products', 'sales_data.product_id', '=', 'products.id')
            ->whereBetween('sales_data.tanggal', [$startDate, $endDate])
            ->when($platformId && $platformId !== 'all', function ($query) use ($platformId) {
                return $query->where('sales_data.platform_id', $platformId);
            })
            ->select(
                'products.category',
                DB::raw('SUM(sales_data.revenue) as total_revenue')
            )
            ->groupBy('products.category')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $productInsight = DB::table('sales_data')
            ->join('products', 'sales_data.product_id', '=', 'products.id')
            ->whereBetween('sales_data.tanggal', [$startDate, $endDate])
            ->when($platformId && $platformId !== 'all', function ($query) use ($platformId) {
                return $query->where('sales_data.platform_id', $platformId);
            })
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
            ->whereBetween('sales_data.tanggal', [$startDate, $endDate])
            ->select(
                'platforms.id',
                'platforms.nama as nama_platform',
                DB::raw('SUM(sales_data.revenue) as total_revenue'),
                DB::raw('SUM(sales_data.units_sold) as total_units_sold')
            )
            ->groupBy('platforms.id', 'platforms.nama')
            ->orderByDesc('total_revenue')
            ->get();

        $topBuyers = DB::table('sales_data')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->when($platformId && $platformId !== 'all', function ($query) use ($platformId) {
                return $query->where('platform_id', $platformId);
            })
            ->select(
                'buyer_email',
                DB::raw('COUNT(*) as total_order'),
                DB::raw('SUM(units_sold) as total_units'),
                DB::raw('SUM(revenue) as total_purchase')
            )
            ->groupBy('buyer_email')
            ->orderByDesc('total_purchase')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($buyer, $index) {
                return [
                    'rank' => $index + 1,
                    'buyer_email' => $buyer->buyer_email,
                    'total_order' => (int) $buyer->total_order,
                    'total_units' => (int) $buyer->total_units,
                    'total_purchase' => (float) $buyer->total_purchase,
                ];
            });

        $performanceMetrics = [
            [
                'metric' => 'Total Revenue',
                'this_period' => $totalRevenue,
                'last_period' => $previousRevenue,
                'change' => $this->calculateChange($totalRevenue, $previousRevenue),
                'type' => 'currency',
            ],
            [
                'metric' => 'Total Orders',
                'this_period' => $totalOrders,
                'last_period' => $previousOrders,
                'change' => $this->calculateChange($totalOrders, $previousOrders),
                'type' => 'number',
            ],
            [
                'metric' => 'Average Order Value',
                'this_period' => $averageOrderValue,
                'last_period' => $previousAov,
                'change' => $this->calculateChange($averageOrderValue, $previousAov),
                'type' => 'currency',
            ],
            [
                'metric' => 'New Customers',
                'this_period' => max($uniqueCustomers - $returningCustomers, 0),
                'last_period' => max($previousUniqueCustomers - $previousReturningCustomers, 0),
                'change' => $this->calculateChange(
                    max($uniqueCustomers - $returningCustomers, 0),
                    max($previousUniqueCustomers - $previousReturningCustomers, 0)
                ),
                'type' => 'number',
            ],
            [
                'metric' => 'Returning Customers',
                'this_period' => $returningCustomers,
                'last_period' => $previousReturningCustomers,
                'change' => $this->calculateChange($returningCustomers, $previousReturningCustomers),
                'type' => 'number',
            ],
        ];

        return response()->json([
            'message' => 'Sales insight data retrieved successfully',
            'summary' => [
                'average_order_value' => $averageOrderValue,
                'customer_retention_rate' => $customerRetentionRate,
                'repeat_purchase_rate' => $repeatPurchaseRate,
                'growth_rate' => $growthRate,
                'total_revenue' => $totalRevenue,
                'total_units_sold' => $totalUnitsSold,
                'total_orders' => $totalOrders,
                'unique_customers' => $uniqueCustomers,
                'returning_customers' => $returningCustomers,
            ],
            'sales_trend' => $salesTrend,
            'category_revenue' => $categoryRevenue,
            'product_insight' => $productInsight,
            'platform_insight' => $platformInsight,
            'top_buyers' => $topBuyers,
            'performance_metrics' => $performanceMetrics,
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