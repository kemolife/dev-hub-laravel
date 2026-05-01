<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\PostManagementController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\UpdateLastSeenAt;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->middleware('throttle:5,1');

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post:slug}', [PostController::class, 'show']);

Route::middleware(['auth:sanctum', UpdateLastSeenAt::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    Route::get('/tokens', [TokenController::class, 'index']);
    Route::post('/tokens', [TokenController::class, 'store']);
    Route::delete('/tokens/{tokenId}', [TokenController::class, 'destroy']);

    Route::post('/posts', [PostManagementController::class, 'store']);
    Route::put('/posts/{post:slug}', [PostManagementController::class, 'update']);
    Route::delete('/posts/{post:slug}', [PostManagementController::class, 'destroy']);
    Route::post('/posts/{post:slug}/publish', [PostManagementController::class, 'publish']);
    Route::post('/posts/{post:slug}/archive', [PostManagementController::class, 'archive']);
});
