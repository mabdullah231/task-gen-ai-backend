<?php

use App\Http\Controllers\AIConfigController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/resend-email', [AuthController::class, 'resendEmail']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-forgot-email', [AuthController::class, 'verifyForgotEmail']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [StatsController::class, 'adminStats']);
    Route::prefix('openai')->group(function () {
        Route::post('/manage-config', [AIConfigController::class, 'manageOpenAIConfig']);
        Route::get('/view-config', [AIConfigController::class, 'viewOpenAIConfig']);
    });

    Route::prefix('userdata')->group(function () {
        Route::get('/get/{id}', [UserController::class, 'getUser']); // Get single user with company
        Route::get('/get-all', [UserController::class, 'getAllUsers']); // Get all users with companies
        Route::put('/toggle-status/{id}', [UserController::class, 'toggleUserStatus']); // Activate/Deactivate user
        Route::put('/make-subadmin/{id}', [UserController::class, 'makeSubadmin']); // Activate/Deactivate user
    });
});

Route::middleware(['auth:sanctum', 'user'])->prefix('user')->group(function () {
    Route::get('/stats', [StatsController::class, 'userStats']);
    Route::prefix('ai')->group(function () {
        Route::post('/generate-response', [AIConfigController::class, 'generateContent']);
    });
});
