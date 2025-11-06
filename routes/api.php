<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\OTPController;
use App\Http\Controllers\API\ContentController;
use App\Http\Controllers\API\ModerationController;
use App\Http\Controllers\API\UserManagementController;
use App\Http\Controllers\API\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// OTP routes
Route::post('/send-otp', [OTPController::class, 'sendOTP']);
Route::post('/verify-otp', [OTPController::class, 'verifyOTP']);
Route::post('/reset-password', [OTPController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth user info
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Article management
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    
    // Category management (admin/editor only)
    Route::middleware('can:manage-content')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });
    
    // Media management
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media', [MediaController::class, 'index']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);
    
    // Content creation and management (all logged-in users)
    Route::prefix('content')->group(function () {
        Route::get('/', [ContentController::class, 'index']);
        Route::post('/', [ContentController::class, 'store']);
        Route::get('/{id}', [ContentController::class, 'show']);
        Route::put('/{id}', [ContentController::class, 'update']);
        Route::delete('/{id}', [ContentController::class, 'destroy']);
        Route::post('/{id}/submit', [ContentController::class, 'submit']);
    });
    
    // Moderation routes (admin and moderator only)
    Route::middleware('role:moderator,admin,superadmin')->prefix('moderation')->group(function () {
        Route::get('/pending', [ModerationController::class, 'pending']);
        Route::post('/articles/{id}/approve', [ModerationController::class, 'approve']);
        Route::post('/articles/{id}/reject', [ModerationController::class, 'reject']);
        Route::post('/articles/{id}/request-changes', [ModerationController::class, 'requestChanges']);
        Route::post('/articles/{id}/publish', [ModerationController::class, 'publish']);
        Route::post('/articles/{id}/unpublish', [ModerationController::class, 'unpublish']);
    });
    
    // User management routes (admin and superadmin only)
    Route::middleware('role:admin,superadmin')->prefix('users')->group(function () {
        Route::post('/{id}/block', [UserManagementController::class, 'blockUser']);
        Route::post('/{id}/unblock', [UserManagementController::class, 'unblockUser']);
        Route::post('/{id}/warn', [UserManagementController::class, 'warnUser']);
        Route::get('/{id}/warnings', [UserManagementController::class, 'getUserWarnings']);
        Route::post('/{id}/assign-role', [UserManagementController::class, 'assignRole']);
        Route::post('/{id}/remove-role', [UserManagementController::class, 'removeRole']);
    });
    
    // User's own warnings
    Route::post('/warnings/{id}/mark-read', [UserManagementController::class, 'markWarningAsRead']);
    
    // Dashboard routes (all authenticated users)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Dashboard management routes (admin/moderator only)
    Route::middleware('role:moderator,admin,superadmin')->group(function () {
        Route::get('/dashboard/users', [DashboardController::class, 'users']);
        Route::get('/dashboard/articles', [DashboardController::class, 'articles']);
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Oshongshoy API is running',
        'timestamp' => now()->toISOString(),
    ]);
});
