<?php

namespace App\Services;

use App\Models\EquipmentAsset;
use App\Models\RepairJob;
use App\Models\Warranty;
use Carbon\Carbon;

class ReportService
{
    public function inventoryReport(array $filters): array
    {
        $query = EquipmentAsset::with(['category', 'manufacturer', 'assetModel', 'location', 'status', 'assignedTo'])
            ->when(!empty($filters['category_id']), fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when(!empty($filters['location_id']), fn ($q) => $q->where('location_id', $filters['location_id']))
            ->when(!empty($filters['status_id']), fn ($q) => $q->where('status_id', $filters['status_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->latest();

        $assets = $query->get();

        $totalValuation = $assets->sum(fn ($a) => (float) ($a->purchase_cost ?? 0));
        $assignedCount = $assets->filter(fn ($a) => !is_null($a->assigned_to_user_id))->count();

        $byCategory = $assets->groupBy(fn ($a) => $a->category?->name ?? 'Uncategorized')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
                'valuation' => number_format((float) $group->sum(fn ($a) => (float) ($a->purchase_cost ?? 0)), 2, '.', ''),
            ])->values()->all();

        $rows = $assets->map(fn (EquipmentAsset $a) => [
            'id' => $a->id,
            'asset_tag' => $a->asset_tag,
            'serial_number' => $a->serial_number,
            'name' => $a->name ?? $a->assetModel?->name ?? '—',
            'category' => $a->category?->name ?? 'Uncategorized',
            'manufacturer' => $a->manufacturer?->name ?? '—',
            'model' => $a->assetModel?->name ?? '—',
            'status' => $a->status?->name ?? '—',
            'location' => $a->location?->name ?? 'Unassigned',
            'assigned_to' => $a->assignedTo?->name ?? 'In Storage',
            'purchase_date' => $a->purchase_date?->toDateString(),
            'purchase_cost' => number_format((float) ($a->purchase_cost ?? 0), 2, '.', ''),
            'warranty_expiry_date' => $a->warranty_expiry_date?->toDateString(),
            'condition' => $a->condition,
        ])->values()->all();

        return [
            'summary' => [
                'total_assets' => $assets->count(),
                'assigned_assets' => $assignedCount,
                'unassigned_assets' => $assets->count() - $assignedCount,
                'total_valuation' => number_format((float) $totalValuation, 2, '.', ''),
            ],
            'breakdown' => $byCategory,
            'rows' => $rows,
        ];
    }

    public function warrantyExpirationReport(array $filters): array
    {
        $today = Carbon::today();
        $thirtyDays = Carbon::today()->addDays(30);

        $query = Warranty::with(['asset.category', 'asset.location', 'asset.manufacturer'])
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate('end_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate('end_date', '<=', $filters['date_to']))
            ->when(!empty($filters['category_id']), fn ($q) => $q->whereHas('asset', fn ($aq) => $aq->where('category_id', $filters['category_id'])))
            ->when(!empty($filters['location_id']), fn ($q) => $q->whereHas('asset', fn ($aq) => $aq->where('location_id', $filters['location_id'])))
            ->when(!empty($filters['status']), function ($q) use ($filters, $today, $thirtyDays) {
                if ($filters['status'] === 'expiring_soon') {
                    $q->whereBetween('end_date', [$today, $thirtyDays]);
                } elseif ($filters['status'] === 'expired') {
                    $q->where('end_date', '<', $today);
                } elseif ($filters['status'] === 'active') {
                    $q->where('end_date', '>', $thirtyDays);
                }
            })
            ->orderBy('end_date', 'asc');

        $warranties = $query->get();

        $rows = $warranties->map(fn (Warranty $w) => [
            'id' => $w->id,
            'asset_tag' => $w->asset?->asset_tag ?? '—',
            'serial_number' => $w->asset?->serial_number ?? '—',
            'asset_name' => $w->asset?->name ?? '—',
            'category' => $w->asset?->category?->name ?? '—',
            'location' => $w->asset?->location?->name ?? '—',
            'provider_name' => $w->provider_name,
            'policy_number' => $w->policy_number ?? 'N/A',
            'warranty_type' => $w->warranty_type ?? 'standard',
            'start_date' => $w->start_date?->toDateString(),
            'end_date' => $w->end_date?->toDateString(),
            'status' => $w->computed_status,
            'days_remaining' => $w->days_remaining,
        ])->values()->all();

        $activeCount = collect($rows)->where('status', 'active')->count();
        $expiringCount = collect($rows)->where('status', 'expiring_soon')->count();
        $expiredCount = collect($rows)->where('status', 'expired')->count();

        return [
            'summary' => [
                'total_warranties' => count($rows),
                'active' => $activeCount,
                'expiring_soon' => $expiringCount,
                'expired' => $expiredCount,
            ],
            'breakdown' => [
                ['name' => 'Active', 'count' => $activeCount],
                ['name' => 'Expiring Soon', 'count' => $expiringCount],
                ['name' => 'Expired', 'count' => $expiredCount],
            ],
            'rows' => $rows,
        ];
    }

    public function repairCostReport(array $filters): array
    {
        $query = RepairJob::with(['asset.category', 'asset.location', 'faultType', 'technician'])
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate('start_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate('start_date', '<=', $filters['date_to']))
            ->when(!empty($filters['category_id']), fn ($q) => $q->whereHas('asset', fn ($aq) => $aq->where('category_id', $filters['category_id'])))
            ->when(!empty($filters['location_id']), fn ($q) => $q->whereHas('asset', fn ($aq) => $aq->where('location_id', $filters['location_id'])))
            ->when(!empty($filters['repair_status']), fn ($q) => $q->where('repair_status', $filters['repair_status']))
            ->when(!empty($filters['repair_center']), fn ($q) => $q->where('repair_center', $filters['repair_center']))
            ->latest();

        $repairs = $query->get();
        $totalCost = $repairs->sum(fn ($r) => (float) ($r->cost ?? 0));
        $avgCost = $repairs->count() > 0 ? $totalCost / $repairs->count() : 0;

        $byStatus = $repairs->groupBy('repair_status')
            ->map(fn ($group, $status) => [
                'name' => ucfirst(str_replace('_', ' ', $status)),
                'count' => $group->count(),
                'cost' => number_format((float) $group->sum(fn ($r) => (float) ($r->cost ?? 0)), 2, '.', ''),
            ])->values()->all();

        $rows = $repairs->map(fn (RepairJob $r) => [
            'id' => $r->id,
            'job_number' => $r->job_number,
            'asset_tag' => $r->asset?->asset_tag ?? '—',
            'asset_name' => $r->asset?->name ?? '—',
            'category' => $r->asset?->category?->name ?? '—',
            'location' => $r->asset?->location?->name ?? '—',
            'fault_type' => $r->faultType?->name ?? 'Hardware Defect',
            'repair_center' => $r->repair_center,
            'vendor_name' => $r->vendor_name ?? 'Internal',
            'repair_status' => $r->repair_status,
            'technician' => $r->technician?->name ?? 'Unassigned',
            'start_date' => $r->start_date?->toDateString(),
            'completion_date' => $r->completion_date?->toDateString(),
            'cost' => number_format((float) ($r->cost ?? 0), 2, '.', ''),
        ])->values()->all();

        return [
            'summary' => [
                'total_jobs' => $repairs->count(),
                'completed_jobs' => $repairs->where('repair_status', 'completed')->count(),
                'total_cost' => number_format((float) $totalCost, 2, '.', ''),
                'average_cost' => number_format((float) $avgCost, 2, '.', ''),
            ],
            'breakdown' => $byStatus,
            'rows' => $rows,
        ];
    }
}
