<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EquipmentAsset;
use App\Models\RepairJob;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim($request->query('q', $request->query('query', '')));

        if (strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'message' => 'Search query too short. Provide at least 2 characters.',
                'data'    => [
                    'assets'     => [],
                    'repairs'    => [],
                    'warranties' => [],
                    'users'      => [],
                    'total'      => 0,
                ],
            ]);
        }

        // 1. Assets Search
        $assets = EquipmentAsset::with(['category', 'manufacturer', 'model', 'status', 'assignedTo'])
            ->where(function ($query) use ($term) {
                $query->where('asset_tag', 'ILIKE', "%{$term}%")
                    ->orWhere('serial_number', 'ILIKE', "%{$term}%")
                    ->orWhere('name', 'ILIKE', "%{$term}%")
                    ->orWhere('processor', 'ILIKE', "%{$term}%")
                    ->orWhere('notes', 'ILIKE', "%{$term}%")
                    ->orWhereHas('model', fn($q) => $q->where('name', 'ILIKE', "%{$term}%")->orWhere('model_number', 'ILIKE', "%{$term}%"))
                    ->orWhereHas('manufacturer', fn($q) => $q->where('name', 'ILIKE', "%{$term}%"))
                    ->orWhereHas('category', fn($q) => $q->where('name', 'ILIKE', "%{$term}%"))
                    ->orWhereHas('assignedTo', fn($q) => $q->where('name', 'ILIKE', "%{$term}%")->orWhere('email', 'ILIKE', "%{$term}%"));
            })
            ->limit(10)
            ->get()
            ->map(fn($asset) => [
                'id'           => $asset->id,
                'type'         => 'asset',
                'title'        => ($asset->model?->name ? "{$asset->model->name} ({$asset->name})" : $asset->name) ?? $asset->asset_tag,
                'subtitle'     => "{$asset->asset_tag} | SN: {$asset->serial_number}",
                'manufacturer' => $asset->manufacturer?->name,
                'model'        => $asset->model?->name,
                'category'     => $asset->category?->name,
                'status'       => $asset->status?->name,
                'assigned_to'  => $asset->assignedTo?->name,
            ]);

        // 2. Repairs Search
        $repairs = RepairJob::with(['asset', 'technician'])
            ->where(function ($query) use ($term) {
                $query->where('job_number', 'ILIKE', "%{$term}%")
                    ->orWhere('fault_description', 'ILIKE', "%{$term}%")
                    ->orWhere('vendor_name', 'ILIKE', "%{$term}%")
                    ->orWhere('diagnostic_notes', 'ILIKE', "%{$term}%")
                    ->orWhere('resolution_summary', 'ILIKE', "%{$term}%")
                    ->orWhereHas('asset', fn($q) => $q->where('asset_tag', 'ILIKE', "%{$term}%")->orWhere('serial_number', 'ILIKE', "%{$term}%"));
            })
            ->limit(10)
            ->get()
            ->map(fn($repair) => [
                'id'        => $repair->id,
                'type'      => 'repair',
                'title'     => $repair->job_number,
                'subtitle'  => $repair->fault_description,
                'status'    => $repair->repair_status,
                'asset_tag' => $repair->asset?->asset_tag,
            ]);

        // 3. Warranties Search
        $warranties = Warranty::with(['asset'])
            ->where(function ($query) use ($term) {
                $query->where('provider_name', 'ILIKE', "%{$term}%")
                    ->orWhere('policy_number', 'ILIKE', "%{$term}%")
                    ->orWhereHas('asset', fn($q) => $q->where('asset_tag', 'ILIKE', "%{$term}%")->orWhere('serial_number', 'ILIKE', "%{$term}%"));
            })
            ->limit(10)
            ->get()
            ->map(fn($warranty) => [
                'id'        => $warranty->id,
                'type'      => 'warranty',
                'title'     => $warranty->provider_name ?? 'OEM Warranty',
                'subtitle'  => 'Policy: ' . ($warranty->policy_number ?? 'N/A'),
                'asset_tag' => $warranty->asset?->asset_tag,
                'end_date'  => $warranty->end_date?->format('Y-m-d'),
            ]);

        // 4. Users Search
        $users = User::where(function ($query) use ($term) {
                $query->where('name', 'ILIKE', "%{$term}%")
                    ->orWhere('email', 'ILIKE', "%{$term}%")
                    ->orWhere('employee_number', 'ILIKE', "%{$term}%")
                    ->orWhere('job_title', 'ILIKE', "%{$term}%");
            })
            ->limit(10)
            ->get()
            ->map(fn($user) => [
                'id'              => $user->id,
                'type'            => 'user',
                'title'           => $user->name,
                'subtitle'        => ($user->job_title ?? 'Staff') . ($user->employee_number ? " ({$user->employee_number})" : ''),
                'email'           => $user->email,
                'employee_number' => $user->employee_number,
            ]);

        $total = $assets->count() + $repairs->count() + $warranties->count() + $users->count();

        return response()->json([
            'success' => true,
            'message' => "Found {$total} matching records.",
            'data'    => [
                'assets'     => $assets,
                'repairs'    => $repairs,
                'warranties' => $warranties,
                'users'      => $users,
                'total'      => $total,
            ],
        ]);
    }
}