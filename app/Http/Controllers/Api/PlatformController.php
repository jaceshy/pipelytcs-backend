<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform;

class PlatformController extends Controller
{
    public function index()
    {
        $platforms = Platform::withCount('products')->get();

        return response()->json([
            'message' => 'Platforms retrieved successfully',
            'data' => $platforms,
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
}