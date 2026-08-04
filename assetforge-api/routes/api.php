<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {

        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {

            Route::post('/logout', [AuthController::class, 'logout']);

            Route::get('/me', [AuthController::class, 'me']);

        });

    });

        Route::middleware([
        'auth:sanctum',
        'permission:settings.manage',
    ])->get('/test-permission', function () {

        return response()->json([
            'success' => true,
            'message' => 'Permission granted',
        ]);

    });

});