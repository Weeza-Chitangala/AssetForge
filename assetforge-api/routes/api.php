<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
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

    // Protected API Routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // User Administration CRUD endpoints
        Route::apiResource('users', UserController::class);

        // Permission Test Route
        Route::middleware('permission:settings.manage')->get('/test-permission', function () {
            return response()->json([
                'success' => true,
                'message' => 'Permission granted',
            ]);
        });

    });

});