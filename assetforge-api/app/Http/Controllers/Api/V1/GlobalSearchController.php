<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EquipmentAsset;
use App\Models\RepairJob;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        abort_if(! $user || ! $tenantId, 403, 'An active tenant context is required for this operation.');

        $term = trim((string) $request->query('q', $request->query('query', '')));

        if (strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'message' => 'Search query too short. Provide at least 2 characters.',
                'data' => [
                    'assets' => [],
                    'repairs' => [],
                    'warranties' => [],
                    'users' => [],
                    'total' => 0,
                ],
            ]);
        }

        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $canViewAssets = $user->can('assets.view');
        $canViewRepairs = $user->can('repairs.view');
        $canViewUsers = $user->can('users.view');

        // 1. Assets Search (requires assets.view; email lookup gated by users.view)
        $assets = collect();
        if ($canViewAssets) {
            $assets = EquipmentAsset::with(['category', 'manufacturer', 'model', 'status', 'assignedTo'])
                ->where(function ($query) use ($term, $likeOp, $canViewUsers) {
                    $query->where('asset_tag', $likeOp, "%{$term}%")
                        ->orWhere('serial_number', $likeOp, "%{$term}%")
                        ->orWhere('name', $likeOp, "%{$term}%")
                        ->orWhere('processor', $likeOp, "%{$term}%")
                        ->orWhere('notes', $likeOp, "%{$term}%")
                        ->orWhereHas('model', fn ($q) => $q->where('name', $likeOp, "%{$term}%")->orWhere('model_number', $likeOp, "%{$term}%"))
                        ->orWhereHas('manufacturer', fn ($q) => $q->where('name', $likeOp, "%{$term}%"))
                        ->orWhereHas('category', fn ($q) => $q->where('name', $likeOp, "%{$term}%"))
                        ->orWhereHas('assignedTo', function ($q) use ($term, $likeOp, $canViewUsers) {
                            $q->where('name', $likeOp, "%{$term}%");
                            if ($canViewUsers) {
                                $q->orWhere('email', $likeOp, "%{$term}%");
                            }
                        });
                })
                ->limit(10)
                ->get()
                ->map(fn ($asset) => [
                    'id' => $asset->id,
                    'type' => 'asset',
                    'title' => ($asset->model?->name ? "{$asset->model->name} ({$asset->name})" : $asset->name) ?? $asset->asset_tag,
                    'subtitle' => "{$asset->asset_tag} | SN: {$asset->serial_number}",
                    'manufacturer' => $asset->manufacturer?->name,
                    'model' => $asset->model?->name,
                    'category' => $asset->category?->name,
                    'status' => $asset->status?->name,
                    'assigned_to' => $asset->assignedTo?->name,
                ]);
        }

        // 2. Repairs Search (requires repairs.view)
        $repairs = collect();
        if ($canViewRepairs) {
            $repairs = RepairJob::with(['asset', 'technician'])
                ->where(function ($query) use ($term, $likeOp) {
                    $query->where('job_number', $likeOp, "%{$term}%")
                        ->orWhere('fault_description', $likeOp, "%{$term}%")
                        ->orWhere('vendor_name', $likeOp, "%{$term}%")
                        ->orWhere('diagnostic_notes', $likeOp, "%{$term}%")
                        ->orWhere('resolution_summary', $likeOp, "%{$term}%")
                        ->orWhereHas('asset', fn ($q) => $q->where('asset_tag', $likeOp, "%{$term}%")->orWhere('serial_number', $likeOp, "%{$term}%"));
                })
                ->limit(10)
                ->get()
                ->map(fn ($repair) => [
                    'id' => $repair->id,
                    'type' => 'repair',
                    'title' => $repair->job_number,
                    'subtitle' => $repair->fault_description,
                    'status' => $repair->repair_status,
                    'asset_tag' => $repair->asset?->asset_tag,
                ]);
        }

        // 3. Warranties Search (requires assets.view)
        $warranties = collect();
        if ($canViewAssets) {
            $warranties = Warranty::with(['asset'])
                ->where(function ($query) use ($term, $likeOp) {
                    $query->where('provider_name', $likeOp, "%{$term}%")
                        ->orWhere('policy_number', $likeOp, "%{$term}%")
                        ->orWhereHas('asset', fn ($q) => $q->where('asset_tag', $likeOp, "%{$term}%")->orWhere('serial_number', $likeOp, "%{$term}%"));
                })
                ->limit(10)
                ->get()
                ->map(fn ($warranty) => [
                    'id' => $warranty->id,
                    'type' => 'warranty',
                    'title' => $warranty->provider_name ?? 'OEM Warranty',
                    'subtitle' => 'Policy: ' . ($warranty->policy_number ?? 'N/A'),
                    'asset_tag' => $warranty->asset?->asset_tag,
                    'end_date' => $warranty->end_date?->format('Y-m-d'),
                ]);
        }

        // 4. Users Search (requires users.view; strictly scoped to current tenant)
        $users = collect();
        if ($canViewUsers) {
            $users = User::where('tenant_id', $tenantId)
                ->where(function ($query) use ($term, $likeOp) {
                    $query->where('name', $likeOp, "%{$term}%")
                        ->orWhere('email', $likeOp, "%{$term}%")
                        ->orWhere('employee_number', $likeOp, "%{$term}%")
                        ->orWhere('job_title', $likeOp, "%{$term}%");
                })
                ->limit(10)
                ->get()
                ->map(fn ($foundUser) => [
                    'id' => $foundUser->id,
                    'type' => 'user',
                    'title' => $foundUser->name,
                    'subtitle' => ($foundUser->job_title ?? 'Staff') . ($foundUser->employee_number ? " ({$foundUser->employee_number})" : ''),
                    'email' => $foundUser->email,
                    'employee_number' => $foundUser->employee_number,
                ]);
        }

        $total = $assets->count() + $repairs->count() + $warranties->count() + $users->count();

        return response()->json([
            'success' => true,
            'message' => "Found {$total} matching records.",
            'data' => [
                'assets' => $assets,
                'repairs' => $repairs,
                'warranties' => $warranties,
                'users' => $users,
                'total' => $total,
            ],
        ]);
    }
}
