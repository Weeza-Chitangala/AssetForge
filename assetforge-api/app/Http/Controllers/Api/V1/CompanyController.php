<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        $companies = Company::with('organization')
            ->whereHas('organization', fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'organization_id' => $company->organization_id,
                'organization_name' => $company->organization?->name,
                'name' => $company->name,
                'code' => $company->code,
                'status' => $company->status,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Companies retrieved successfully.',
            'data' => $companies,
        ]);
    }
}
