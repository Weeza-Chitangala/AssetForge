<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\ManufacturerRequest;
use App\Http\Resources\ManufacturerResource;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufacturerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Manufacturer::query();
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        if ($request->filled('search')) {
            $search = $request->search;
            // Group OR conditions so TenantScope's tenant_id predicate applies to every search branch.
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('name', $likeOp, "%{$search}%")
                    ->orWhere('code', $likeOp, "%{$search}%");
            });
        }

        $manufacturers = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Manufacturers retrieved successfully.',
            'data' => ManufacturerResource::collection($manufacturers),
        ]);
    }

    public function store(ManufacturerRequest $request): JsonResponse
    {
        $manufacturer = Manufacturer::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer created successfully.',
            'data' => new ManufacturerResource($manufacturer),
        ], 201);
    }

    public function show(Manufacturer $manufacturer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Manufacturer retrieved successfully.',
            'data' => new ManufacturerResource($manufacturer),
        ]);
    }

    public function update(ManufacturerRequest $request, Manufacturer $manufacturer): JsonResponse
    {
        $manufacturer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer updated successfully.',
            'data' => new ManufacturerResource($manufacturer),
        ]);
    }

    public function destroy(Manufacturer $manufacturer): JsonResponse
    {
        $manufacturer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer deleted successfully.',
        ]);
    }
}