<?php

use App\Http\Controllers\JobController;

Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/all', [JobController::class, 'allJobs']);
    Route::get('/jobs/filters', [JobController::class, 'jobFilters']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::post('/jobs/upload/documents', [JobController::class, 'uploadDocuments']);
    Route::get('/jobs/documents/{id}', [JobController::class, 'getDocuments']);
    Route::delete('/jobs/documents/{id}', [JobController::class, 'deleteDocument']);

    Route::get('/jobs-export-excel', [JobController::class, 'exportJobs']);

});

Route::middleware(['auth:sanctum', 'role:manager'])->group(function () {
    // Create a job
    Route::post('jobs', [JobController::class, 'store']);
    // update job status
    Route::post('jobs/status', [JobController::class, 'changeStatus']);

    // Update a job
    Route::put('jobs/{id}', [JobController::class, 'update']);

// Delete a job
    Route::delete('jobs/{id}', [JobController::class, 'destroy']);
});
