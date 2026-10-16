<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MaintenanceRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])
    ->name('register');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index'])
        ->name('maintenance-requests.index');

    Route::get('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'show'])
        ->name('maintenance-requests.show');

    Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store'])
        ->name('maintenance-requests.store');

    Route::get('/maintenance-requests/{id}/history', [MaintenanceRequestController::class, 'history'])
        ->name('maintenance-requests.history');
});
