<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\FeatureFlagController;
use App\Http\Controllers\Api\V1\FeedbackController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\PostManagementController;
use App\Http\Controllers\Api\V1\ReactionController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use App\Http\Middleware\UpdateLastSeenAt;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->middleware('throttle:5,1');

Route::get('/search', SearchController::class);
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post:slug}', [PostController::class, 'show']);
Route::get('/posts/{post:slug}/comments', [CommentController::class, 'index']);

// Stripe webhook — outside auth middleware, no CSRF (Cashier handles signature verification)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{tag:slug}', [TagController::class, 'show']);

Route::get('/features', FeatureFlagController::class);
Route::post('/feedback', [FeedbackController::class, 'store']);

Route::middleware(['auth:sanctum', UpdateLastSeenAt::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    Route::get('/tokens', [TokenController::class, 'index']);
    Route::post('/tokens', [TokenController::class, 'store']);
    Route::delete('/tokens/{tokenId}', [TokenController::class, 'destroy']);

    Route::post('/posts', [PostManagementController::class, 'store'])->middleware('idempotent');
    Route::put('/posts/{post:slug}', [PostManagementController::class, 'update']);
    Route::delete('/posts/{post:slug}', [PostManagementController::class, 'destroy']);
    Route::post('/posts/{post:slug}/publish', [PostManagementController::class, 'publish']);
    Route::post('/posts/{post:slug}/archive', [PostManagementController::class, 'archive']);

    Route::post('/posts/{post:slug}/comments', [CommentController::class, 'store']);
    Route::put('/posts/{post:slug}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/posts/{post:slug}/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/posts/{post:slug}/reactions', [ReactionController::class, 'toggle']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);

    Route::get('/onboarding', [OnboardingController::class, 'show']);

    // Webhooks
    Route::get('/webhooks', [WebhookEndpointController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks', [WebhookEndpointController::class, 'store'])->name('webhooks.store')->middleware('idempotent');
    Route::put('/webhooks/{webhookEndpoint}', [WebhookEndpointController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/{webhookEndpoint}/test', [WebhookEndpointController::class, 'test'])->name('webhooks.test');

    // Billing
    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
});
