<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VesselType;
use Illuminate\Http\JsonResponse;

class VesselTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = VesselType::withCount('vessels')->get();
        return response()->json($types);
    }
}
