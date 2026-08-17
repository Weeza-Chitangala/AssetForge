<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaultTypeResource;
use App\Models\FaultType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaultTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $faultTypes = FaultType::where('status', 'active')->get();

        return response()->json([
            'success' => true,
            'message' => 'Fault types retrieved.',
            'data' => FaultTypeResource::collection($faultTypes),
        ]);
    }
}