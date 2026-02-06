<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

// Public routes
Route::post('/auth/send-verification', [AuthController::class, 'sendVerification']);
Route::post('/auth/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
});

Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS is working!']);
});