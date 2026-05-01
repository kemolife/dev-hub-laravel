<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming Stripe webhook events by updating local subscription state.
 *
 * The Cashier webhook controller handles the heavy lifting (signature verification,
 * subscription model updates). This action handles downstream effects: syncing
 * the `plan` column and dispatching notifications.
 */
class HandleWebhookAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload, string $type): void
    {
        match ($type) {
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload),
            'invoice.payment_failed' => $this->handlePaymentFailed($payload),
            'invoice.payment_succeeded' => $this->handlePaymentSucceeded($payload),
            default => null,
        };
    }

    /** @param array<string, mixed> $payload */
    private function handleSubscriptionUpdated(array $payload): void
    {
        $user = $this->findUserByStripeId($payload['data']['object']['customer'] ?? '');

        if ($user === null) {
            return;
        }

        $status = $payload['data']['object']['status'] ?? '';

        if (in_array($status, ['active', 'trialing'], true)) {
            $planKey = $this->resolvePlanKey($payload);
            $user->update(['plan' => $planKey]);
        }
    }

    /** @param array<string, mixed> $payload */
    private function handleSubscriptionDeleted(array $payload): void
    {
        $user = $this->findUserByStripeId($payload['data']['object']['customer'] ?? '');

        if ($user === null) {
            return;
        }

        $user->update(['plan' => 'free']);
    }

    /** @param array<string, mixed> $payload */
    private function handlePaymentFailed(array $payload): void
    {
        $user = $this->findUserByStripeId($payload['data']['object']['customer'] ?? '');

        if ($user === null) {
            return;
        }

        Log::warning('Payment failed for user', ['user_id' => $user->id]);
    }

    /** @param array<string, mixed> $payload */
    private function handlePaymentSucceeded(array $payload): void
    {
        $user = $this->findUserByStripeId($payload['data']['object']['customer'] ?? '');

        if ($user === null) {
            return;
        }

        Log::info('Payment succeeded for user', ['user_id' => $user->id]);
    }

    private function findUserByStripeId(string $stripeId): ?User
    {
        if ($stripeId === '') {
            return null;
        }

        return User::where('stripe_id', $stripeId)->first();
    }

    /**
     * Resolve the plan key from subscription items price IDs.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolvePlanKey(array $payload): string
    {
        /** @var array<string, mixed>[] $items */
        $items = $payload['data']['object']['items']['data'] ?? [];
        $priceId = $items[0]['price']['id'] ?? '';

        /** @var array<string, array{stripe_price_id?: string}> $plans */
        $plans = config('plans', []);

        foreach ($plans as $planKey => $planConfig) {
            if (($planConfig['stripe_price_id'] ?? null) === $priceId) {
                return $planKey;
            }
        }

        return 'pro';
    }
}
