<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\LocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Location::with('company');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'ILIKE', "%{$search}%")->orWhere('code', 'ILIKE', "%{$search}%");
        }

        $locations = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Locations retrieved successfully.',
            'data' => LocationResource::collection($locations),
        ]);
    }

    public function store(LocationRequest $request): JsonResponse
    {
        $location = Location::create($request->validated());
        $location->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully.',
            'data' => new LocationResource($location),
        ], 201);
    }

    public function show(Location $location): JsonResponse
    {
        $location->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Location retrieved successfully.',
            'data' => new LocationResource($location),
        ]);
    }

    public function update(LocationRequest $request, Location $location): JsonResponse
    {
        $location->update($request->validated());
        $location->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'data' => new LocationResource($location),
        ]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully.',
        ]);
    }
}