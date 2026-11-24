<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query = Department::with('company')
            ->whereHas('company.organization', fn ($q) => $q->where('tenant_id', $tenantId));

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('name', $likeOp, "%{$search}%")
                    ->orWhere('code', $likeOp, "%{$search}%");
            });
        }

        $departments = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Departments retrieved successfully.',
            'data' => DepartmentResource::collection($departments),
        ]);
    }

    public function store(DepartmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->ensureCompanyBelongsToTenant($data['company_id'], $request->user()?->tenant_id);

        $department = Department::create($data);
        $department->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'data' => new DepartmentResource($department),
        ], 201);
    }

    public function show(Request $request, Department $department): JsonResponse
    {
        $this->ensureDepartmentBelongsToTenant($department, $request->user()?->tenant_id);
        $department->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Department retrieved successfully.',
            'data' => new DepartmentResource($department),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $this->ensureDepartmentBelongsToTenant($department, $tenantId);

        $data = $request->validated();
        $this->ensureCompanyBelongsToTenant($data['company_id'], $tenantId);

        $department->update($data);
        $department->load('company');

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'data' => new DepartmentResource($department),
        ]);
    }

    public function destroy(Request $request, Department $department): JsonResponse
    {
        $this->ensureDepartmentBelongsToTenant($department, $request->user()?->tenant_id);
        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }

    protected function ensureCompanyBelongsToTenant(string $companyId, ?string $tenantId): void
    {
        $exists = Company::where('id', $companyId)
            ->whereHas('organization', fn ($q) => $q->where('tenant_id', $tenantId))
            ->exists();

        abort_unless($exists, 404, 'Company not found for current tenant.');
    }

    protected function ensureDepartmentBelongsToTenant(Department $department, ?string $tenantId): void
    {
        $department->loadMissing('company.organization');
        abort_unless(
            $department->company?->organization?->tenant_id === $tenantId,
            404,
            'Department not found for current tenant.'
        );
    }
}
