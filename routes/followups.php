<?php
use App\Http\Controllers\FollowupController;

Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    Route::get('/followups', [FollowupController::class, 'index']);
    Route::get('/followups/filters', [FollowupController::class, 'followupFilters']);
    Route::get('/followups/{id}', [FollowupController::class, 'show']);
    Route::post('/followups/check/date-time', [FollowupController::class, 'checkDateTime']);
    Route::get('/followups-export-excel', [FollowupController::class, 'exportFollowups']);

});
Route::middleware(['auth:sanctum', 'role:executive,manager'])->group(function () {
    Route::post('/followups', [FollowupController::class, 'create']);
    Route::put('/followups/{followup}', [FollowupController::class, 'update']);
    Route::delete('/followups/{followup}', [FollowupController::class, 'destroy']);
    Route::post('/followups/{id}/update', [FollowupController::class, 'updateStatus']);
});
