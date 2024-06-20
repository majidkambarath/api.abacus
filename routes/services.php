<?php
use App\Http\Controllers\ServiceController;

Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
});
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
});
