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

        return [
            'plan' => $plan,
            'status' => $subscription?->stripe_status,
            'trial_ends_at' => $user->trial_ends_at?->toIso8601String(),
            'renews_at' => $subscription?->asStripeSubscription()->current_period_end
                ? date('c', $subscription->asStripeSubscription()->current_period_end)
                : null,
            'cancelled_at' => $subscription?->ends_at?->toIso8601String(),
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
            'limits' => [
                'posts_per_month' => $limits->postsPerMonth(),
                'api_access' => $limits->hasApiAccess(),
            ],
        ];
    }
}
