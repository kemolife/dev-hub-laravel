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
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $limits = PlanLimits::for($user);

        /** @var array<string, mixed>|null $planConfig */
        $planConfig = config("plans.{$user->plan}", config('plans.free'));

        return [
            'plan' => $user->plan ?? 'free',
            'plan_name' => $planConfig['name'] ?? 'Free',
            'trial_ends_at' => $user->trial_ends_at?->toIso8601String(),
            'on_trial' => $user->trial_ends_at !== null && $user->trial_ends_at->isFuture(),
            'subscribed' => $user->subscribed(),
            'subscription_status' => $user->subscription()?->stripe_status,
            'limits' => [
                'posts_per_month' => $limits->postsPerMonth(),
                'api_access' => $limits->hasApiAccess(),
            ],
        ];
    }
}
