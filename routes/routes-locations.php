<?php
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RouteController;

Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    Route::get('routes', [RouteController::class, 'index']);
});
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('routes/{route}', [RouteController::class, 'show']);
    Route::post('routes', [RouteController::class, 'store']);
    Route::put('routes/{route}', [RouteController::class, 'update']);
    Route::delete('routes/{route}', [RouteController::class, 'destroy']);

    // Locations
    Route::post('routes/{route}/locations', [RouteController::class, 'storeLocation']);
    Route::put('routes/{route}/locations/{location}', [RouteController::class, 'updateLocation']);
    Route::delete('routes/locations/{location}', [RouteController::class, 'destroyLocation']);
});
