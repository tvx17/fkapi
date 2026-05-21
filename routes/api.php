<?php

Route::prefix('v1')->group(function () {

    // Öffentliche Routen
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Geschützte Routen
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // Benutzerverwaltung (nur für Admins)
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
        });

        // Query Builder
        Route::post('/query', [QueryBuilderController::class, 'execute']);
    });
});
