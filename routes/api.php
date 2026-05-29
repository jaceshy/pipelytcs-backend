<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SalesInsightController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Test koneksi database untuk development
Route::get('/test-db', function () {
    return response()->json([
        'message' => 'Database connected',
        'users' => DB::table('users')->count(),
        'products' => DB::table('products')->count(),
        'platforms' => DB::table('platforms')->count(),
        'sales_data' => DB::table('sales_data')->count(),
    ]);
});

// Auth public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Semua route aplikasi wajib login
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Read-only routes: admin dan team boleh akses
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    Route::get('/platforms', [PlatformController::class, 'index']);
    Route::get('/platforms/{id}', [PlatformController::class, 'show']);

    Route::get('/sales-insights', [SalesInsightController::class, 'index']);
    Route::get('/settings', [SettingController::class, 'index']);

    // Admin-only routes: team tidak boleh akses
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        Route::post('/purchases', [PurchaseController::class, 'store']);
    });
});