<?php

use App\Http\Controllers\Api\V1\AssetBatchController;
use App\Http\Controllers\Api\V1\AssetLabelController;
use App\Http\Controllers\Api\V1\AssetLifecycleController;
use App\Http\Controllers\Api\V1\AssetModelController;
use App\Http\Controllers\Api\V1\AssetStatusController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EquipmentAssetController;
use App\Http\Controllers\Api\V1\FaultTypeController;
use App\Http\Controllers\Api\V1\GlobalSearchController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ManufacturerController;
use App\Http\Controllers\Api\V1\RepairJobController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarrantyController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public Health Check
    Route::get('/health', function () {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'ok',
                'database' => 'connected',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'database' => 'disconnected',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    });

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::middleware(['auth:sanctum', 'active.session'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // Authenticated API Routes
    Route::middleware(['auth:sanctum', 'active.session'])->group(function () {

        // Search & Dashboard
        Route::get('search', [GlobalSearchController::class, 'search']);
        Route::get('dashboard/summary', [DashboardController::class, 'summary'])->middleware('permission:dashboard.view');

        // Audit Logs
        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view');
        Route::get('audit-logs/{type}/{id}', [AuditLogController::class, 'entityAudit'])->middleware('permission:audit.view');

        // Reports & Exports (AF-045 to AF-049)
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('inventory', [ReportController::class, 'inventory']);
            Route::get('inventory/export', [ReportController::class, 'exportInventory']);
            Route::get('warranties', [ReportController::class, 'warranties']);
            Route::get('warranties/export', [ReportController::class, 'exportWarranties']);
            Route::get('repairs', [ReportController::class, 'repairs']);
            Route::get('repairs/export', [ReportController::class, 'exportRepairs']);
        });

        // User Administration CRUD endpoints protected by Spatie permissions
        Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

        // Batch Asset Operations (defined before wildcard assets/{asset})
        Route::post('assets/import', [AssetBatchController::class, 'import'])->middleware('permission:assets.create');
        Route::get('assets/export', [AssetBatchController::class, 'export'])->middleware('permission:assets.view');

        // Equipment Asset Resource Endpoints
        Route::get('assets', [EquipmentAssetController::class, 'index'])->middleware('permission:assets.view');
        Route::post('assets', [EquipmentAssetController::class, 'store'])->middleware('permission:assets.create');
        Route::get('assets/{asset}', [EquipmentAssetController::class, 'show'])->middleware('permission:assets.view');
        Route::put('assets/{asset}', [EquipmentAssetController::class, 'update'])->middleware('permission:assets.update');
        Route::delete('assets/{asset}', [EquipmentAssetController::class, 'destroy'])->middleware('permission:assets.delete');

        // Asset Label & QR Code Endpoints
        Route::get('assets/{asset}/qr', [AssetLabelController::class, 'qrCode'])->name('assets.qr')->middleware('permission:assets.view');
        Route::get('assets/{asset}/label-data', [AssetLabelController::class, 'printPayload'])->name('assets.label-data')->middleware('permission:assets.view');

        // Asset Lifecycle Actions (custody operations require dedicated assets.transfer permission)
        Route::post('assets/{asset}/checkout', [AssetLifecycleController::class, 'checkout'])->middleware('permission:assets.transfer');
        Route::post('assets/{asset}/checkin', [AssetLifecycleController::class, 'checkin'])->middleware('permission:assets.transfer');
        Route::post('assets/{asset}/transfer', [AssetLifecycleController::class, 'transfer'])->middleware('permission:assets.transfer');
        Route::get('assets/{asset}/history', [AssetLifecycleController::class, 'history'])->middleware('permission:assets.view');

        // Warranty Endpoints
        Route::get('warranties', [WarrantyController::class, 'index'])->middleware('permission:assets.view');
        Route::post('warranties', [WarrantyController::class, 'store'])->middleware('permission:assets.create');
        Route::get('warranties/{warranty}', [WarrantyController::class, 'show'])->middleware('permission:assets.view');
        Route::put('warranties/{warranty}', [WarrantyController::class, 'update'])->middleware('permission:assets.update');
        Route::delete('warranties/{warranty}', [WarrantyController::class, 'destroy'])->middleware('permission:assets.delete');

        // Reference Data Lookup Endpoints (read for authenticated users, write for settings.manage)
        Route::get('asset-statuses', [AssetStatusController::class, 'index']);
        Route::get('companies', [CompanyController::class, 'index']);
        Route::get('fault-types', [FaultTypeController::class, 'index'])->middleware('permission:assets.view');

        foreach ([
            'categories' => CategoryController::class,
            'manufacturers' => ManufacturerController::class,
            'asset-models' => AssetModelController::class,
            'locations' => LocationController::class,
            'departments' => DepartmentController::class,
            'suppliers' => SupplierController::class,
        ] as $prefix => $controller) {
            Route::get($prefix, [$controller, 'index']);
            Route::get("{$prefix}/{" . str_replace('-', '_', str()->singular($prefix)) . '}', [$controller, 'show']);
            Route::middleware('permission:settings.manage')->group(function () use ($prefix, $controller) {
                $param = str_replace('-', '_', str()->singular($prefix));
                Route::post($prefix, [$controller, 'store']);
                Route::put("{$prefix}/{{$param}}", [$controller, 'update']);
                Route::patch("{$prefix}/{{$param}}", [$controller, 'update']);
                Route::delete("{$prefix}/{{$param}}", [$controller, 'destroy']);
            });
        }

        // Repair Management
        Route::get('repairs', [RepairJobController::class, 'index'])->middleware('permission:repairs.view');
        Route::post('repairs', [RepairJobController::class, 'store'])->middleware('permission:repairs.create');
        Route::get('repairs/{repairJob}', [RepairJobController::class, 'show'])->middleware('permission:repairs.view');
        Route::put('repairs/{repairJob}', [RepairJobController::class, 'update'])->middleware('permission:repairs.update');
        Route::post('repairs/{repairJob}/progress', [RepairJobController::class, 'addProgress'])->middleware('permission:repairs.update');

        // Routes removed: /test-permission debug route is disabled in production security hardening.
    });
});
