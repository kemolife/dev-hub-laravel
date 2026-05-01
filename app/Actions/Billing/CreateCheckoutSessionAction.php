<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\User;
use InvalidArgumentException;
use Laravel\Cashier\Checkout;

class CreateCheckoutSessionAction
{
    public function execute(User $user, string $planKey): string
    {
        /** @var array{stripe_price_id?: string}|null $planConfig */
        $planConfig = config("plans.{$planKey}");

        if ($planConfig === null) {
            throw new InvalidArgumentException("Unknown plan: {$planKey}");
        }

        $stripePriceId = $planConfig['stripe_price_id'] ?? null;

        if ($stripePriceId === null) {
            throw new InvalidArgumentException("Plan '{$planKey}' has no Stripe price ID configured.");
        }

        /** @var Checkout $checkout */
        $checkout = $user->newSubscription('default', $stripePriceId)
            ->checkout([
                'success_url' => config('app.url').'/billing/success',
                'cancel_url' => config('app.url').'/billing',
            ]);

        return $checkout->url ?? '';
    }
}
