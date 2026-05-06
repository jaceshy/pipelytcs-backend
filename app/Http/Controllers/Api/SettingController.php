<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

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
}