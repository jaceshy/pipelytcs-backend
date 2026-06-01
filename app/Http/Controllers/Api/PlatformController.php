<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformController extends Controller
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

        $platforms = Platform::all();

        $platformMetrics = $platforms->map(function ($platform) use ($startDate, $endDate, $previousStartDate, $previousEndDate) {
            $currentQuery = DB::table('sales_data')
                ->where('platform_id', $platform->id)
                ->whereBetween('tanggal', [$startDate, $endDate]);

            $previousQuery = DB::table('sales_data')
                ->where('platform_id', $platform->id)
                ->whereBetween('tanggal', [$previousStartDate, $previousEndDate]);

            $totalRevenue = (float) (clone $currentQuery)->sum('revenue');
            $totalUnitsSold = (int) (clone $currentQuery)->sum('units_sold');
            $totalOrders = (int) (clone $currentQuery)->count();

            $previousRevenue = (float) (clone $previousQuery)->sum('revenue');

            $aov = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $growthRate = $this->calculateChange($totalRevenue, $previousRevenue);

            // Estimasi karena belum ada tabel traffic/visitor asli
            $estimatedTraffic = ($totalOrders * 100) + ($totalUnitsSold * 10);
            $conversionRate = $estimatedTraffic > 0 ? ($totalOrders / $estimatedTraffic) * 100 : 0;

            // Estimasi fee platform untuk visualisasi
            $feeRate = $this->getEstimatedFeeRate($platform->nama);
            $feeTotal = $totalRevenue * $feeRate;

            return [
                'id' => $platform->id,
                'nama_platform' => $platform->nama,
                'total_revenue' => $totalRevenue,
                'total_units_sold' => $totalUnitsSold,
                'total_orders' => $totalOrders,
                'aov' => $aov,
                'growth_rate' => $growthRate,
                'estimated_traffic' => $estimatedTraffic,
                'conversion_rate' => $conversionRate,
                'fee_rate' => $feeRate,
                'fee_total' => $feeTotal,
            ];
        })->values();

        $dailyRevenue = DB::table('sales_data')
            ->join('platforms', 'sales_data.platform_id', '=', 'platforms.id')
            ->whereBetween('sales_data.tanggal', [$startDate, $endDate])
            ->select(
                'sales_data.tanggal',
                'platforms.id as platform_id',
                'platforms.nama as nama_platform',
                DB::raw('SUM(sales_data.revenue) as total_revenue'),
                DB::raw('SUM(sales_data.units_sold) as total_units_sold')
            )
            ->groupBy('sales_data.tanggal', 'platforms.id', 'platforms.nama')
            ->orderBy('sales_data.tanggal')
            ->get();

        $bestPlatformToday = DB::table('sales_data')
            ->join('platforms', 'sales_data.platform_id', '=', 'platforms.id')
            ->where('sales_data.tanggal', $endDate)
            ->select(
                'platforms.id',
                'platforms.nama as nama_platform',
                DB::raw('SUM(sales_data.revenue) as total_revenue')
            )
            ->groupBy('platforms.id', 'platforms.nama')
            ->orderByDesc('total_revenue')
            ->first();

        if (!$bestPlatformToday) {
            $bestPlatformToday = collect($platformMetrics)
                ->sortByDesc('total_revenue')
                ->first();
        }

        $highestConversion = collect($platformMetrics)
            ->sortByDesc('conversion_rate')
            ->first();

        $bestGrowthRate = collect($platformMetrics)
            ->sortByDesc('growth_rate')
            ->first();

        return response()->json([
            'message' => 'Platform comparison data retrieved successfully',
            'summary' => [
                'best_platform_today' => $bestPlatformToday,
                'highest_conversion' => $highestConversion,
                'best_growth_rate' => $bestGrowthRate,
            ],
            'platform_metrics' => $platformMetrics,
            'daily_revenue' => $dailyRevenue,
        ]);
    }

    public function show($id)
    {
        $platform = Platform::with(['products', 'salesData'])->find($id);

        if (!$platform) {
            return response()->json([
                'message' => 'Platform not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Platform detail retrieved successfully',
            'data' => $platform,
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

    private function getEstimatedFeeRate($platformName)
    {
        $name = strtolower($platformName);

        if (str_contains($name, 'shopee')) {
            return 0.045;
        }

        if (str_contains($name, 'tokopedia')) {
            return 0.035;
        }

        if (str_contains($name, 'tiktok')) {
            return 0.04;
        }

        if (str_contains($name, 'instagram')) {
            return 0.025;
        }

        return 0.03;
    }
}