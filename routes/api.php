<?php

use App\Http\Controllers\AIConfigController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TaskBotController;
use App\Http\Controllers\PlansController;
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
    Route::prefix('ai')->group(function () {
        Route::post('/manage-config', [AIConfigController::class, 'manageAIConfig']);
        Route::get('/view-config', [AIConfigController::class, 'viewAIConfig']);
    });

    Route::prefix('userdata')->group(function () {
        Route::get('/get/{id}', [UserController::class, 'getUser']); 
        Route::get('/get-all', [UserController::class, 'getAllUsers']); 
        Route::put('/toggle-status/{id}', [UserController::class, 'toggleUserStatus']); 
        Route::put('/make-subadmin/{id}', [UserController::class, 'makeSubadmin']); 
    });
});

Route::middleware(['auth:sanctum', 'user'])->prefix('user')->group(function () {
    Route::get('/stats', [StatsController::class, 'userStats']);
    Route::get('/details', [UserController::class, 'getUserDetails']);
    Route::put('/update', [UserController::class, 'updateUserDetails']);
    Route::prefix('taskbot')->group(function () {
        Route::post('/generate-suggestions', [TaskBotController::class, 'suggestionGeneration']);
    });
    Route::prefix('plans')->group(function () {
        Route::post('/all', [PlansController::class, 'getAllPlans']);
        Route::post('/single/id', [PlansController::class, 'getSinglePlan']);
    });
});
