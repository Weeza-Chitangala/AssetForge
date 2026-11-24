<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\InventoryReportExport;
use App\Exports\RepairCostReportExport;
use App\Exports\WarrantyReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportFilterRequest;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function inventory(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->reportService->inventoryReport($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventory report generated successfully.',
            'data' => $report,
        ]);
    }

    public function exportInventory(ReportFilterRequest $request): BinaryFileResponse|Response
    {
        $filters = $request->validated();
        $format = $filters['format'] ?? 'xlsx';
        $report = $this->reportService->inventoryReport($filters);
        $filename = 'assetforge-inventory-report-' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.inventory', [
                'report' => $report,
                'generatedAt' => now()->toDateTimeString(),
                'tenantName' => $request->user()?->tenant?->name ?? 'AssetForge Tenant',
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new InventoryReportExport($report['rows']),
            $filename . '.' . $format
        );
    }

    public function warranties(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->reportService->warrantyExpirationReport($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warranty expiration report generated successfully.',
            'data' => $report,
        ]);
    }

    public function exportWarranties(ReportFilterRequest $request): BinaryFileResponse|Response
    {
        $filters = $request->validated();
        $format = $filters['format'] ?? 'xlsx';
        $report = $this->reportService->warrantyExpirationReport($filters);
        $filename = 'assetforge-warranty-report-' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.warranties', [
                'report' => $report,
                'generatedAt' => now()->toDateTimeString(),
                'tenantName' => $request->user()?->tenant?->name ?? 'AssetForge Tenant',
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new WarrantyReportExport($report['rows']),
            $filename . '.' . $format
        );
    }

    public function repairs(ReportFilterRequest $request): JsonResponse
    {
        $report = $this->reportService->repairCostReport($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Repair cost report generated successfully.',
            'data' => $report,
        ]);
    }

    public function exportRepairs(ReportFilterRequest $request): BinaryFileResponse|Response
    {
        $filters = $request->validated();
        $format = $filters['format'] ?? 'xlsx';
        $report = $this->reportService->repairCostReport($filters);
        $filename = 'assetforge-repair-cost-report-' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.repairs', [
                'report' => $report,
                'generatedAt' => now()->toDateTimeString(),
                'tenantName' => $request->user()?->tenant?->name ?? 'AssetForge Tenant',
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new RepairCostReportExport($report['rows']),
            $filename . '.' . $format
        );
    }
}
