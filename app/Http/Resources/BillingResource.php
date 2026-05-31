<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Support\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property User $resource */
class BillingResource extends JsonResource
{
    private function resolvePlanFromSubscription(?string $stripePriceId): string
    {
        if ($stripePriceId === null) {
            return 'free';
        }

        foreach (['pro', 'pro_annual'] as $key) {
            if (config("plans.{$key}.stripe_price_id") === $stripePriceId) {
                return $key;
            }
        }

        return 'free';
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $limits = PlanLimits::for($user);

        $subscription = $user->subscription();

        $plan = $this->resolvePlanFromSubscription($subscription?->stripe_price);

        $stripeSubscription = $subscription?->asStripeSubscription();
        $renewsAt = null;
        if ($stripeSubscription && isset($stripeSubscription->current_period_end)) {
            $renewsAt = date('c', $stripeSubscription->current_period_end);
        }

        return [
            'plan' => $plan,
            'plan_name' => config("plans.{$plan}.name", ucfirst($plan)),
            'subscription_status' => $subscription?->stripe_status,
            'subscribed' => $subscription !== null && $subscription->active(),
            'trial_ends_at' => $user->trial_ends_at?->toIso8601String(),
            'on_trial' => $user->trial_ends_at !== null && $user->trial_ends_at->isFuture(),
            'renews_at' => $renewsAt,
            'cancelled_at' => $subscription?->ends_at?->toIso8601String(),
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
            'limits' => [
                'posts_per_month' => $limits->postsPerMonth(),
                'api_access' => $limits->hasApiAccess(),
            ],
        ];
    }
}
