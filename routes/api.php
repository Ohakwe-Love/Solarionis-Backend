<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;

// Public routes
Route::post('/auth/send-verification', [AuthController::class, 'sendVerification']);
Route::post('/auth/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Dashboard routes
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/portfolio', [DashboardController::class, 'portfolio']);
    Route::get('/dashboard/projects', [DashboardController::class, 'projects']);
});

Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS is working!']);
});