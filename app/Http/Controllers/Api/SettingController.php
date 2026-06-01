<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Settings retrieved successfully',
            'data' => [
                'app_name' => 'Pipelytcs',
                'version' => '1.0.0',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
            ],
        ]);
    }

    public function exportCsv()
    {
        $fileName = 'pipelytcs-sales-data-' . now()->format('Y-m-d-His') . '.csv';

        $salesData = DB::table('sales_data')
            ->join('products', 'sales_data.product_id', '=', 'products.id')
            ->join('platforms', 'sales_data.platform_id', '=', 'platforms.id')
            ->select(
                'sales_data.tanggal',
                'sales_data.buyer_email',
                'products.nama_produk',
                'products.sku',
                'products.category',
                'platforms.nama as platform',
                'sales_data.units_sold',
                'sales_data.price',
                'sales_data.revenue'
            )
            ->orderByDesc('sales_data.tanggal')
            ->get();

        return response()->streamDownload(function () use ($salesData) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tanggal',
                'Buyer Email',
                'Product Name',
                'SKU',
                'Category',
                'Platform',
                'Units Sold',
                'Price',
                'Revenue',
            ]);

            foreach ($salesData as $row) {
                fputcsv($handle, [
                    $row->tanggal,
                    $row->buyer_email,
                    $row->nama_produk,
                    $row->sku,
                    $row->category,
                    $row->platform,
                    $row->units_sold,
                    $row->price,
                    $row->revenue,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}