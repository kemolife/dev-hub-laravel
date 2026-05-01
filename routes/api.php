<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\PostManagementController;
use App\Http\Controllers\Api\V1\ReactionController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\CheckNotSuspended;
use App\Http\Middleware\UpdateLastSeenAt;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->middleware('throttle:5,1');

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post:slug}', [PostController::class, 'show']);
Route::get('/posts/{post:slug}/comments', [CommentController::class, 'index']);

// Stripe webhook — outside auth middleware, no CSRF (Cashier handles signature verification)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{tag:slug}', [TagController::class, 'show']);

Route::middleware(['auth:sanctum', UpdateLastSeenAt::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    Route::get('/tokens', [TokenController::class, 'index']);
    Route::post('/tokens', [TokenController::class, 'store']);
    Route::delete('/tokens/{tokenId}', [TokenController::class, 'destroy']);

    Route::middleware([CheckNotSuspended::class])->group(function (): void {
        Route::post('/posts', [PostManagementController::class, 'store']);
        Route::put('/posts/{post:slug}', [PostManagementController::class, 'update']);
        Route::delete('/posts/{post:slug}', [PostManagementController::class, 'destroy']);
        Route::post('/posts/{post:slug}/publish', [PostManagementController::class, 'publish']);
        Route::post('/posts/{post:slug}/archive', [PostManagementController::class, 'archive']);

        Route::post('/posts/{post:slug}/comments', [CommentController::class, 'store']);
        Route::put('/posts/{post:slug}/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/posts/{post:slug}/comments/{comment}', [CommentController::class, 'destroy']);

        Route::post('/posts/{post:slug}/reactions', [ReactionController::class, 'toggle']);

        Route::post('/reports/{type}/{id}', [ReportController::class, 'store'])->middleware('throttle:5,60');
    });

    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
});
