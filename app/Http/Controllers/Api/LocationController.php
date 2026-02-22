<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::with('images')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($locations);
    }

    public function show(Location $location): JsonResponse
    {
        if (!$location->is_active) {
            return response()->json(['message' => 'الموقع غير متاح'], 404);
        }

        $location->load('images');

        return response()->json($location);
    }
}
