<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchManagerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\FollowupReasonController;
use App\Http\Controllers\JobDocumentTypesController;
use App\Http\Controllers\LeadStageController;
use App\Http\Controllers\LeadSourceController;

// only for admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Get all branches
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/all', [BranchController::class, 'allBranches']);

// Get a single branch by ID
    Route::get('/branches/{id}', [BranchController::class, 'show']);

// Create a new branch
    Route::post('/branches', [BranchController::class, 'store']);

// Update a branch by ID
    Route::put('/branches/{id}', [BranchController::class, 'update']);

// Delete a branch by ID
    Route::delete('/branches/{id}', [BranchController::class, 'destroy']);


    Route::get('/managers', [BranchManagerController::class, 'index']);
    Route::get('/managers/all', [BranchManagerController::class, 'allManagers']);

// Get a single branch by ID
    Route::get('/managers/{id}', [BranchManagerController::class, 'show']);

// Create a new branch
    Route::post('/managers', [BranchManagerController::class, 'store']);

// Update a branch by ID
    Route::put('/managers/{id}', [BranchManagerController::class, 'update']);

// Delete a branch by ID
    Route::delete('/managers/{id}', [BranchManagerController::class, 'destroy']);


// Get a single reasons by ID
    Route::get('/reasons/{id}', [FollowupReasonController::class, 'show']);

// Create a new reasons
    Route::post('/reasons', [FollowupReasonController::class, 'store']);

// Update a reasons by ID
    Route::put('/reasons/{id}', [FollowupReasonController::class, 'update']);

// Delete a reasons by ID
    Route::delete('/reasons/{id}', [FollowupReasonController::class, 'destroy']);


// Get a single job_document_types by ID
    Route::get('/job_document_types/{id}', [JobDocumentTypesController::class, 'show']);

// Create a new job_document_types
    Route::post('/job_document_types', [JobDocumentTypesController::class, 'store']);

// Update a job_document_types by ID
    Route::put('/job_document_types/{id}', [JobDocumentTypesController::class, 'update']);

// Delete a job_document_types by ID
    Route::delete('/job_document_types/{id}', [JobDocumentTypesController::class, 'destroy']);


// Get a single lead stage by ID
    Route::get('/lead_stages/{id}', [LeadStageController::class, 'show']);

// Create a new lead stage
    Route::post('/lead_stages', [LeadStageController::class, 'store']);

// Update a lead stage by ID
    Route::put('/lead_stages/{id}', [LeadStageController::class, 'update']);

// Delete a lead stage by ID
    Route::delete('/lead_stages/{id}', [LeadStageController::class, 'destroy']);


// Get a single lead sources by ID
    Route::get('/lead_sources/{id}', [LeadSourceController::class, 'show']);

// Create a new lead sources
    Route::post('/lead_sources', [LeadSourceController::class, 'store']);

// Update a lead sources by ID
    Route::put('/lead_sources/{id}', [LeadSourceController::class, 'update']);

// Delete a lead sources by ID
    Route::delete('/lead_sources/{id}', [LeadSourceController::class, 'destroy']);


});

Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    // follow up reasons
    Route::get('/reasons', [FollowupReasonController::class, 'index']);
    Route::get('/job_document_types', [JobDocumentTypesController::class, 'index']);
    Route::get('/lead_stages', [LeadStageController::class, 'index']);
    Route::get('/lead_sources', [LeadSourceController::class, 'index']);

});
