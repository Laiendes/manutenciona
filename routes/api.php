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

    Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store'])
        ->name('maintenance-requests.store');
});
