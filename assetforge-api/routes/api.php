<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\EquipmentAssetController;
use App\Http\Controllers\Api\V1\AssetLifecycleController;
use App\Http\Controllers\Api\V1\WarrantyController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // Authenticated API Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // User Administration CRUD endpoints protected by Spatie permissions
        Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

        // Equipment Asset Resource Endpoints
        Route::get('assets', [EquipmentAssetController::class, 'index'])->middleware('permission:assets.view');
        Route::post('assets', [EquipmentAssetController::class, 'store'])->middleware('permission:assets.create');
        Route::get('assets/{asset}', [EquipmentAssetController::class, 'show'])->middleware('permission:assets.view');
        Route::put('assets/{asset}', [EquipmentAssetController::class, 'update'])->middleware('permission:assets.update');
        Route::delete('assets/{asset}', [EquipmentAssetController::class, 'destroy'])->middleware('permission:assets.delete');

        // Asset Lifecycle Actions
        Route::post('assets/{asset}/checkout', [AssetLifecycleController::class, 'checkout'])->middleware('permission:assets.update');
        Route::post('assets/{asset}/checkin', [AssetLifecycleController::class, 'checkin'])->middleware('permission:assets.update');
        Route::post('assets/{asset}/transfer', [AssetLifecycleController::class, 'transfer'])->middleware('permission:assets.update');
        Route::get('assets/{asset}/history', [AssetLifecycleController::class, 'history'])->middleware('permission:assets.view');

        // Warranty Endpoints
        Route::apiResource('warranties', WarrantyController::class);

        // Permission Test Route
        Route::middleware('permission:settings.manage')->get('/test-permission', function () {
            return response()->json([
                'success' => true,
                'message' => 'Permission granted',
            ]);
        });

    });

});