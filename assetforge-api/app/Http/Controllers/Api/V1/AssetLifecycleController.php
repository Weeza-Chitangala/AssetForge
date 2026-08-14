<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\CheckinAssetRequest;
use App\Http\Requests\Asset\CheckoutAssetRequest;
use App\Http\Requests\Asset\TransferAssetRequest;
use App\Http\Resources\AssetMovementResource;
use App\Http\Resources\EquipmentAssetResource;
use App\Models\AssetMovement;
use App\Models\AssetStatus;
use App\Models\EquipmentAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetLifecycleController extends Controller
{
    protected array $movementRelations = [
        'fromUser', 'toUser', 'fromLocation', 'toLocation',
        'fromDepartment', 'toDepartment', 'fromStatus', 'toStatus', 'performer'
    ];

    /**
     * View full timeline history of an asset.
     */
    public function history(EquipmentAsset $asset): JsonResponse
    {
        $movements = $asset->movements()->with($this->movementRelations)->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Asset movement history retrieved.',
            'data' => AssetMovementResource::collection($movements)->response()->getData(true),
        ]);
    }

    /**
     * Checkout (Assign) asset to a user.
     */
    public function checkout(CheckoutAssetRequest $request, EquipmentAsset $asset): JsonResponse
    {
        $data = $request->validated();

        $assignedStatus = AssetStatus::where('code', 'ASSIGNED')->first();

        DB::transaction(function () use ($asset, $data, $request, $assignedStatus) {
            // 1. Record movement history
            AssetMovement::create([
                'tenant_id' => $request->user()->tenant_id,
                'equipment_asset_id' => $asset->id,
                'action_type' => 'checkout',
                'from_user_id' => $asset->assigned_to_user_id,
                'from_location_id' => $asset->location_id,
                'from_department_id' => $asset->department_id,
                'from_status_id' => $asset->status_id,
                'to_user_id' => $data['user_id'],
                'to_location_id' => $data['location_id'] ?? $asset->location_id,
                'to_department_id' => $data['department_id'] ?? $asset->department_id,
                'to_status_id' => $assignedStatus?->id ?? $asset->status_id,
                'movement_date' => $data['checkout_date'] ?? now(),
                'condition' => $data['condition'] ?? $asset->condition,
                'notes' => $data['notes'] ?? 'Asset checked out to user',
                'performed_by' => $request->user()->id,
            ]);

            // 2. Update asset state
            $asset->update([
                'assigned_to_user_id' => $data['user_id'],
                'assigned_date' => $data['checkout_date'] ?? now(),
                'location_id' => $data['location_id'] ?? $asset->location_id,
                'department_id' => $data['department_id'] ?? $asset->department_id,
                'status_id' => $assignedStatus?->id ?? $asset->status_id,
                'condition' => $data['condition'] ?? $asset->condition,
                'updated_by' => $request->user()->id,
            ]);
        });

        $asset->load(['category', 'manufacturer', 'assetModel', 'location', 'status', 'assignedTo']);

        return response()->json([
            'success' => true,
            'message' => 'Asset checked out successfully.',
            'data' => new EquipmentAssetResource($asset),
        ]);
    }

    /**
     * Checkin (Return) asset from user to storage.
     */
    public function checkin(CheckinAssetRequest $request, EquipmentAsset $asset): JsonResponse
    {
        $data = $request->validated();

        $availableStatus = AssetStatus::where('code', 'AVAILABLE')->first();

        DB::transaction(function () use ($asset, $data, $request, $availableStatus) {
            // 1. Record movement
            AssetMovement::create([
                'tenant_id' => $request->user()->tenant_id,
                'equipment_asset_id' => $asset->id,
                'action_type' => 'checkin',
                'from_user_id' => $asset->assigned_to_user_id,
                'from_location_id' => $asset->location_id,
                'from_department_id' => $asset->department_id,
                'from_status_id' => $asset->status_id,
                'to_user_id' => null,
                'to_location_id' => $data['location_id'] ?? $asset->location_id,
                'to_department_id' => $asset->department_id,
                'to_status_id' => $data['status_id'] ?? ($availableStatus?->id ?? $asset->status_id),
                'movement_date' => $data['checkin_date'] ?? now(),
                'condition' => $data['condition'] ?? $asset->condition,
                'notes' => $data['notes'] ?? 'Asset returned to inventory',
                'performed_by' => $request->user()->id,
            ]);

            // 2. Update asset state
            $asset->update([
                'assigned_to_user_id' => null,
                'assigned_date' => null,
                'location_id' => $data['location_id'] ?? $asset->location_id,
                'status_id' => $data['status_id'] ?? ($availableStatus?->id ?? $asset->status_id),
                'condition' => $data['condition'] ?? $asset->condition,
                'updated_by' => $request->user()->id,
            ]);
        });

        $asset->load(['category', 'manufacturer', 'assetModel', 'location', 'status', 'assignedTo']);

        return response()->json([
            'success' => true,
            'message' => 'Asset checked in successfully.',
            'data' => new EquipmentAssetResource($asset),
        ]);
    }

    /**
     * Transfer asset between locations / departments.
     */
    public function transfer(TransferAssetRequest $request, EquipmentAsset $asset): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($asset, $data, $request) {
            AssetMovement::create([
                'tenant_id' => $request->user()->tenant_id,
                'equipment_asset_id' => $asset->id,
                'action_type' => 'transfer',
                'from_user_id' => $asset->assigned_to_user_id,
                'from_location_id' => $asset->location_id,
                'from_department_id' => $asset->department_id,
                'from_status_id' => $asset->status_id,
                'to_user_id' => $asset->assigned_to_user_id,
                'to_location_id' => $data['location_id'] ?? $asset->location_id,
                'to_department_id' => $data['department_id'] ?? $asset->department_id,
                'to_status_id' => $asset->status_id,
                'movement_date' => $data['transfer_date'] ?? now(),
                'condition' => $asset->condition,
                'notes' => $data['notes'] ?? 'Asset transferred',
                'performed_by' => $request->user()->id,
            ]);

            $asset->update([
                'location_id' => $data['location_id'] ?? $asset->location_id,
                'department_id' => $data['department_id'] ?? $asset->department_id,
                'company_id' => $data['company_id'] ?? $asset->company_id,
                'updated_by' => $request->user()->id,
            ]);
        });

        $asset->load(['category', 'manufacturer', 'assetModel', 'location', 'status', 'assignedTo']);

        return response()->json([
            'success' => true,
            'message' => 'Asset transferred successfully.',
            'data' => new EquipmentAssetResource($asset),
        ]);
    }
}