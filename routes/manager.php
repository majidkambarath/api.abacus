<?php

use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\LeadController;

Route::middleware(['auth:sanctum', 'role:manager'])->group(function () {

    Route::get('/executives', [ExecutiveController::class, 'index']);
    Route::get('/executives/all', [ExecutiveController::class, 'allExecutives']);

// Get a single branch by ID
    Route::get('/executives/{id}', [ExecutiveController::class, 'show']);

// Create a new branch
    Route::post('/executives', [ExecutiveController::class, 'store']);
    // documents

// Update a branch by ID
    Route::put('/executives/{id}', [ExecutiveController::class, 'update']);

// Delete a branch by ID
    Route::delete('/executives/{id}', [ExecutiveController::class, 'destroy']);
});
Route::post('/executives/documents', [ExecutiveController::class, 'uploadDocuments']);


