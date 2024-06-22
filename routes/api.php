<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    try {
        $user = $request->user();
        $user->load('roles');

        $return = [
            "name" => $user->name,
            "id" => $user->id,
            "role" => $user->roles[0]->name ?? 'No Role'
        ];

        if ($user->hasRole('manager')) {
            $user->load('managedBranch');
            $return['branch'] = $user->managedBranch ? $user->managedBranch->name : 'No Branch';
        }

        if ($user->hasRole('executive')) {
            $return['branch'] = $user->branches[0]->name ?? 'No Branch';
        }

        return response()->json($return);
    } catch (\Exception $e) {
        \Log::error('Error fetching user data: ' . $e->getMessage());
        return response()->json(['error' => 'Server error'], 500);
    }
});

include __DIR__ . '/admin.php';
include __DIR__ . '/manager.php';
include __DIR__ . '/leads.php';
include __DIR__ . '/jobs.php';
include __DIR__ . '/routes-locations.php';
include __DIR__ . '/services.php';
include __DIR__ . '/followups.php';
include __DIR__ . '/reports.php';

Route::middleware(['auth:sanctum', 'role:executive,manager,admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/change_password', [DashboardController::class, 'changePassword']);
});

Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::get('/update-photos', [PublicController::class, 'updatePhotos']);