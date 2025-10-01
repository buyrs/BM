<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Protected routes requiring authentication
    Route::middleware(['auth:sanctum', 'api.auth'])->group(function () {
        
        // Authentication management
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('profile', [AuthController::class, 'profile']);
            Route::delete('tokens', [AuthController::class, 'revokeAllTokens']);
            Route::get('tokens', [AuthController::class, 'tokens']);
        });

        // Properties API
        Route::prefix('properties')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\PropertyController::class, 'index']);
            Route::get('/statistics', [\App\Http\Controllers\Api\PropertyController::class, 'statistics']);
            Route::get('/{id}', [\App\Http\Controllers\Api\PropertyController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\Api\PropertyController::class, 'store'])->middleware('api.role:admin,ops');
            Route::put('/{id}', [\App\Http\Controllers\Api\PropertyController::class, 'update'])->middleware('api.role:admin,ops');
            Route::delete('/{id}', [\App\Http\Controllers\Api\PropertyController::class, 'destroy'])->middleware('api.role:admin');
        });

        // Missions API
        Route::prefix('missions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\MissionController::class, 'index']);
            Route::get('/statistics', [\App\Http\Controllers\Api\MissionController::class, 'statistics']);
            Route::get('/{id}', [\App\Http\Controllers\Api\MissionController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\Api\MissionController::class, 'store'])->middleware('api.role:admin,ops');
            Route::put('/{id}', [\App\Http\Controllers\Api\MissionController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\MissionController::class, 'destroy'])->middleware('api.role:admin');
        });

        // Checklists API
        Route::prefix('checklists')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ChecklistController::class, 'index']);
            Route::get('/statistics', [\App\Http\Controllers\Api\ChecklistController::class, 'statistics']);
            Route::get('/{id}', [\App\Http\Controllers\Api\ChecklistController::class, 'show']);
            Route::put('/{checklistId}/items/{itemId}', [\App\Http\Controllers\Api\ChecklistController::class, 'updateItem']);
            Route::post('/{id}/complete', [\App\Http\Controllers\Api\ChecklistController::class, 'complete']);
        });

        // Users API
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\UserController::class, 'index'])->middleware('api.role:admin,ops');
            Route::get('/statistics', [\App\Http\Controllers\Api\UserController::class, 'statistics'])->middleware('api.role:admin,ops');
            Route::get('/role/{role}', [\App\Http\Controllers\Api\UserController::class, 'byRole'])->middleware('api.role:admin,ops');
            Route::get('/{id}', [\App\Http\Controllers\Api\UserController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\Api\UserController::class, 'store'])->middleware('api.role:admin');
            Route::put('/{id}', [\App\Http\Controllers\Api\UserController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\UserController::class, 'destroy'])->middleware('api.role:admin');
        });

        // Notifications API
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::get('/statistics', [\App\Http\Controllers\Api\NotificationController::class, 'statistics']);
            Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
            Route::post('/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
            Route::get('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'show']);
            Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
            Route::post('/{id}/unread', [\App\Http\Controllers\Api\NotificationController::class, 'markAsUnread']);
            Route::post('/{id}/action', [\App\Http\Controllers\Api\NotificationController::class, 'takeAction']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
        });

        // Files API
        Route::prefix('files')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\FileController::class, 'index']);
            Route::get('/statistics', [\App\Http\Controllers\Api\FileController::class, 'statistics']);
            Route::get('/{id}', [\App\Http\Controllers\Api\FileController::class, 'show']);
            Route::get('/{id}/download', [\App\Http\Controllers\Api\FileController::class, 'download']);
            Route::post('/', [\App\Http\Controllers\Api\FileController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\FileController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\FileController::class, 'destroy'])->middleware('api.role:admin,ops');
        });

        // Analytics API
        Route::prefix('analytics')->middleware('api.role:admin')->group(function () {
            Route::get('/usage', [\App\Http\Controllers\Api\AnalyticsController::class, 'usage']);
            Route::get('/real-time', [\App\Http\Controllers\Api\AnalyticsController::class, 'realTime']);
        });
    });
});

// Health check endpoint
Route::get('health', [\App\Http\Controllers\Api\AnalyticsController::class, 'health']);

// API information endpoint
Route::get('info', function () {
    return response()->json([
        'success' => true,
        'message' => 'Property Management API',
        'version' => 'v1',
        'documentation' => url('/api/documentation'),
        'endpoints' => [
            'auth' => '/api/v1/auth',
            'properties' => '/api/v1/properties',
            'missions' => '/api/v1/missions',
            'checklists' => '/api/v1/checklists',
            'users' => '/api/v1/users',
            'notifications' => '/api/v1/notifications',
            'files' => '/api/v1/files',
        ],
    ]);
});