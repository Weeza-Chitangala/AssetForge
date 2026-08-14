<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\StoreEquipmentAssetRequest;
use App\Http\Requests\Asset\UpdateEquipmentAssetRequest;
use App\Http\Resources\EquipmentAssetResource;
use App\Models\EquipmentAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentAssetController extends Controller
{
    protected array $relations = [
        'category',
        'manufacturer',
        'assetModel',
        'location',
        'status',
        'assignedTo',
        'organization',
        'company',
        'department'
    ];

    /**
     * Display a listing of equipment assets.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EquipmentAsset::with($this->relations);

        // Search filter (asset_tag, serial_number, name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('asset_tag', 'ILIKE', "%{$search}%")
                  ->orWhere('serial_number', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        // Filtering
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('manufacturer_id')) {
            $query->where('manufacturer_id', $request->manufacturer_id);
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->assigned_to_user_id);
        }

        $assets = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Assets retrieved successfully.',
            'data' => EquipmentAssetResource::collection($assets)->response()->getData(true),
        ]);
    }

    /**
     * Store a newly created asset.
     */
    public function store(StoreEquipmentAssetRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['tenant_id']) && $request->user()) {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $data['created_by'] = $request->user()?->id;

        $asset = EquipmentAsset::create($data);
        $asset->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Equipment asset registered successfully.',
            'data' => new EquipmentAssetResource($asset),
        ], 201);
    }

    /**
     * Display the specified asset.
     */
    public function show(EquipmentAsset $asset): JsonResponse
    {
        $asset->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Asset details retrieved successfully.',
            'data' => new EquipmentAssetResource($asset),
        ]);
    }

    /**
     * Update the specified asset.
     */
    public function update(UpdateEquipmentAssetRequest $request, EquipmentAsset $asset): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()?->id;

        $asset->update($data);
        $asset->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully.',
            'data' => new EquipmentAssetResource($asset),
        ]);
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(EquipmentAsset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully.',
        ]);
    }
}