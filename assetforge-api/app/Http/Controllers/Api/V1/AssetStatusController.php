<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetStatus;
use Illuminate\Http\JsonResponse;

class AssetStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $statuses = AssetStatus::orderBy('name')->get([
            'id',
            'name',
            'code',
            'color',
            'is_deployable',
            'is_archived',
            'description',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset statuses retrieved successfully.',
            'data' => $statuses,
        ]);
    }
}
