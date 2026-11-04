<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetMovement;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\EquipmentAsset;
use App\Models\RepairJob;
use App\Models\Warranty;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = Carbon::today();
        $thirtyDays = Carbon::today()->addDays(30);

        // Core KPIs
        $totalAssets = EquipmentAsset::count();
        $inRepairCount = RepairJob::whereIn('repair_status', ['pending', 'diagnosing', 'in_repair', 'parts_on_order'])->count();
        $activeWarranties = Warranty::where('end_date', '>=', $today)->count();
        $expiringWarranties = Warranty::whereBetween('end_date', [$today, $thirtyDays])->count();
        $expiredWarranties = Warranty::where('end_date', '<', $today)->count();
        $totalValuation = EquipmentAsset::sum('purchase_cost') ?? 0;
        $totalRepairSpend = RepairJob::sum('cost') ?? 0;

        // Breakdown by Status
        $assetsByStatus = AssetStatus::all()->map(function ($status) {
            return [
                'id'    => $status->id,
                'name'  => $status->name,
                'color' => $status->color,
                'count' => EquipmentAsset::where('status_id', $status->id)->count(),
            ];
        });

        // Breakdown by Category
        $assetsByCategory = Category::all()->map(function ($cat) {
            return [
                'id'    => $cat->id,
                'name'  => $cat->name,
                'code'  => $cat->code,
                'count' => EquipmentAsset::where('category_id', $cat->id)->count(),
            ];
        });

        // Recent Repairs
        $recentRepairs = RepairJob::with(['asset', 'faultType'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'job_number' => $r->job_number,
                'asset_tag'  => $r->asset?->asset_tag,
                'fault'      => $r->faultType?->name ?? 'Hardware defect',
                'status'     => $r->repair_status,
                'created_at' => $r->created_at?->diffForHumans(),
            ]);

        // Recent Custody Movements
        $recentMovements = AssetMovement::with(['asset', 'performer'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'asset_tag'   => $m->asset?->asset_tag,
                'action_type' => $m->action_type,
                'performer'   => $m->performer?->name,
                'notes'       => $m->notes,
                'created_at'  => $m->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard analytics retrieved successfully.',
            'data'    => [
                'kpis' => [
                    'total_assets'        => $totalAssets,
                    'total_valuation'     => number_format((float) $totalValuation, 2, '.', ''),
                    'assets_in_repair'    => $inRepairCount,
                    'total_repair_spend'  => number_format((float) $totalRepairSpend, 2, '.', ''),
                    'active_warranties'   => $activeWarranties,
                    'expiring_warranties' => $expiringWarranties,
                    'expired_warranties'  => $expiredWarranties,
                ],
                'breakdowns' => [
                    'by_status'   => $assetsByStatus,
                    'by_category' => $assetsByCategory,
                ],
                'activity' => [
                    'recent_repairs'   => $recentRepairs,
                    'recent_movements' => $recentMovements,
                ],
            ],
        ]);
    }
}