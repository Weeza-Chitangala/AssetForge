<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\AssetModelRequest;
use App\Http\Resources\AssetModelResource;
use App\Models\AssetModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetModelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AssetModel::with(['manufacturer', 'category']);

        if ($request->filled('manufacturer_id')) {
            $query->where('manufacturer_id', $request->manufacturer_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ILIKE', "%{$search}%")->orWhere('model_number', 'ILIKE', "%{$search}%");
        }

        $models = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Asset models retrieved successfully.',
            'data' => AssetModelResource::collection($models),
        ]);
    }

    public function store(AssetModelRequest $request): JsonResponse
    {
        $model = AssetModel::create($request->validated());
        $model->load(['manufacturer', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Asset model created successfully.',
            'data' => new AssetModelResource($model),
        ], 201);
    }

    public function show(AssetModel $assetModel): JsonResponse
    {
        $assetModel->load(['manufacturer', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Asset model retrieved successfully.',
            'data' => new AssetModelResource($assetModel),
        ]);
    }

    public function update(AssetModelRequest $request, AssetModel $assetModel): JsonResponse
    {
        $assetModel->update($request->validated());
        $assetModel->load(['manufacturer', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Asset model updated successfully.',
            'data' => new AssetModelResource($assetModel),
        ]);
    }

    public function destroy(AssetModel $assetModel): JsonResponse
    {
        $assetModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset model deleted successfully.',
        ]);
    }
}