<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();

    // Eager load the roles relationship
    $user->load('roles');


    $return = [
        "name" => $user->name,
        "id" => $user->id,
        "role" => $user->roles[0]["name"]
    ];

    if($user->hasRole('manager')){
        $user->load('managedBranch');
        $return['branch'] = $user->managedBranch?->name;
    }
    if($user->hasRole('executive')){
        $return['branch'] = $user->branches[0]->name;
    }

    return $return;
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

Route::get('/update-photos', [\App\Http\Controllers\PublicController::class, 'updatePhotos']);
//Route::get('/delete-control', [\App\Http\Controllers\PublicController::class, 'deleteControllerFolder']);
