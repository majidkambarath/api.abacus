<?php
use App\Http\Controllers\LeadController;

// only for admin
Route::middleware(['auth:sanctum', 'role:executive,manager'])->group(function () {
// Create a new branch
    Route::post('/leads', [LeadController::class, 'store']);
    Route::post('/leads/upload', [LeadController::class, 'uploadFile']);

// Update a branch by ID
    Route::put('/leads/{id}', [LeadController::class, 'update']);

// Delete a branch by ID
    Route::delete('/leads/{id}', [LeadController::class, 'destroy']);

    Route::get('/business/check/{number}', [LeadController::class, 'checkBusiness']);

});
Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/all', [LeadController::class, 'allLeads']);
    // Get a single branch by ID
    Route::get('/leads/filters', [LeadController::class, 'LeadFilters']);
    Route::get('/leads/{id}', [LeadController::class, 'show']);
    Route::put('/leads/stage/{id}', [LeadController::class, 'changeStage']);
    Route::get('/leads-stages/{id}', [LeadController::class, 'getLeadStageHistory']);

    Route::get('/leads-export-excel', [LeadController::class, 'exportLeads']);
    Route::get('/daily-report', [LeadController::class, 'generateDailyReport']);
});

Route::middleware(['auth:sanctum', 'role:manager'])->group(function () {
    Route::put('/leads/{id}/service', [LeadController::class, 'updateService']);
    // change status
    Route::post('/leads/change-status/{id}', [LeadController::class, 'changeStatus']);
});
//Route::middleware(['auth:sanctum', 'role:manager,admin'])->group(function () {
//
//});
