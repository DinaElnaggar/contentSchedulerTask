<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PlatformController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route to verify API is working
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// Public routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::get('/active-platforms', [UserController::class, 'getActivePlatforms']);
    Route::post('/toggle-platforms', [UserController::class, 'togglePlatforms']);

    // Platform routes
    Route::get('/platforms', [PlatformController::class, 'index']);
    Route::post('/platforms', [PlatformController::class, 'store']);
    Route::put('/platforms/{platform}', [PlatformController::class, 'update']);
    Route::delete('/platforms/{platform}', [PlatformController::class, 'destroy']);
    Route::post('/platforms/{platform}/toggle', [PlatformController::class, 'toggle']);

    // Post routes
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
}); 