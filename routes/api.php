<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SalesInsightController;
use App\Http\Controllers\Api\SettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sini semua route API ditempatkan. Termasuk CRUD produk, purchase, 
| platform, dashboard, login/register, settings, sales insight, dan Low Stock Alert.
|
*/

// Route test koneksi database
Route::get('/test-db', function () {
    return response()->json([
        'message' => 'Database connected',
        'users' => DB::table('users')->count(),
        'products' => DB::table('products')->count(),
        'platforms' => DB::table('platforms')->count(),
        'sales_data' => DB::table('sales_data')->count(),
    ]);
});

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// --------------------
// Product Routes
// --------------------

// Low Stock Alert
// HARUS di atas apiResource supaya tidak tertangkap sebagai {product} oleh route show
Route::get('/products/low-stock', [ProductController::class, 'lowStock']);

// CRUD Product
Route::apiResource('products', ProductController::class);

// --------------------
// Platform Routes
// --------------------
Route::get('/platforms', [PlatformController::class, 'index']);
Route::get('/platforms/{id}', [PlatformController::class, 'show']);

// --------------------
// Sales Insight
// --------------------
Route::get('/sales-insights', [SalesInsightController::class, 'index']);

// --------------------
// Settings
// --------------------
Route::get('/settings', [SettingController::class, 'index']);