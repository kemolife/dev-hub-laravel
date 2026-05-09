<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Laravel\Cashier\Events\WebhookHandled;

class SyncUserPlanOnSubscriptionChange
{
    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? null;

        if (! in_array($type, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted'], true)) {
            return;
        }

        $stripeCustomerId = $event->payload['data']['object']['customer'] ?? null;

        if (! $stripeCustomerId) {
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            return;
        }

        $stripePriceId = $event->payload['data']['object']['items']['data'][0]['price']['id'] ?? null;
        $status = $event->payload['data']['object']['status'] ?? null;

        $plan = 'free';
        if (in_array($status, ['active', 'trialing'], true)) {
            foreach (['pro', 'pro_annual'] as $key) {
                if (config("plans.{$key}.stripe_price_id") === $stripePriceId) {
                    $plan = $key;
                    break;
                }
            }
        }

        $user->update(['plan' => $plan]);
    }
}
