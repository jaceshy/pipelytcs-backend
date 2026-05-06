<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SalesInsightController;
use App\Http\Controllers\Api\SettingController;

Route::get('/test-db', function () {
    return response()->json([
        'message' => 'Database connected',
        'users' => DB::table('users')->count(),
        'products' => DB::table('products')->count(),
        'platforms' => DB::table('platforms')->count(),
        'sales_data' => DB::table('sales_data')->count(),
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/products', [ProductController::class, 'index']);

Route::get('/platforms', [PlatformController::class, 'index']);
Route::get('/platforms/{id}', [PlatformController::class, 'show']);

Route::get('/sales-insights', [SalesInsightController::class, 'index']);

Route::get('/settings', [SettingController::class, 'index']);