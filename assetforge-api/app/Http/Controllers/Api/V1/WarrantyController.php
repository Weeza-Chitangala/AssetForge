<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warranty\StoreWarrantyRequest;
use App\Http\Requests\Warranty\UpdateWarrantyRequest;
use App\Http\Resources\WarrantyResource;
use App\Models\EquipmentAsset;
use App\Models\Warranty;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    /**
     * List all warranties across tenant with filter for expiring soon.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warranty::with(['asset.manufacturer', 'asset.assetModel']);

        if ($request->filled('status')) {
            if ($request->status === 'expiring_soon') {
                $query->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(30)]);
            } elseif ($request->status === 'expired') {
                $query->where('end_date', '<', Carbon::today());
            } elseif ($request->status === 'active') {
                $query->where('end_date', '>=', Carbon::today()->addDays(30));
            }
        }

        if ($request->filled('equipment_asset_id')) {
            $query->where('equipment_asset_id', $request->equipment_asset_id);
        }

        $warranties = $query->orderBy('end_date', 'asc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Warranties retrieved successfully.',
            'data' => WarrantyResource::collection($warranties)->response()->getData(true),
        ]);
    }

    /**
     * Store a new warranty contract.
     */
    public function store(StoreWarrantyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['created_by'] = $request->user()->id;

        $warranty = Warranty::create($data);
        $warranty->load('asset');

        // Sync warranty expiry date to parent asset
        $warranty->asset?->update([
            'warranty_expiry_date' => $warranty->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Warranty contract registered successfully.',
            'data' => new WarrantyResource($warranty),
        ], 201);
    }

    /**
     * Show single warranty.
     */
    public function show(Warranty $warranty): JsonResponse
    {
        $warranty->load('asset.manufacturer');

        return response()->json([
            'success' => true,
            'message' => 'Warranty details retrieved.',
            'data' => new WarrantyResource($warranty),
        ]);
    }

    /**
     * Update warranty.
     */
    public function update(UpdateWarrantyRequest $request, Warranty $warranty): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $warranty->update($data);
        $warranty->load('asset');

        if (!empty($data['end_date'])) {
            $warranty->asset?->update([
                'warranty_expiry_date' => $data['end_date'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Warranty updated successfully.',
            'data' => new WarrantyResource($warranty),
        ]);
    }

    /**
     * Delete warranty.
     */
    public function destroy(Warranty $warranty): JsonResponse
    {
        $warranty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warranty record deleted.',
        ]);
    }
}