<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\GradeLevel;
use Illuminate\Http\JsonResponse;

class MiscController extends Controller
{
    public function governorates(): JsonResponse
    {
        $governorates = Governorate::orderBy('name', 'asc')->get(['id', 'name']);
        return response()->json([
            'status' => 'success',
            'data' => $governorates
        ]);
    }

    public function gradeLevels(): JsonResponse
    {
        $gradeLevels = GradeLevel::orderBy('sort_order', 'asc')->get(['id', 'name', 'sort_order']);
        return response()->json([
            'status' => 'success',
            'data' => $gradeLevels
        ]);
    }
}
