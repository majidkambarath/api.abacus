<?php

Route::middleware(['auth:sanctum', 'role:manager,admin'])->group(function () {

    Route::get('/reports/lead_job_reports', [\App\Http\Controllers\ReportsController::class, 'leadsAndJobs']);

});
