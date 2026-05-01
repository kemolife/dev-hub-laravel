<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\OnboardingStep;
use App\Models\User;
use Illuminate\Support\Carbon;

readonly class CompleteOnboardingStepAction
{
    public function execute(User $user, OnboardingStep $step): void
    {
        $steps = $user->onboarding_steps ?? [];

        if (in_array($step->value, $steps, strict: true)) {
            return;
        }

        $steps[] = $step->value;
        $user->onboarding_steps = $steps;

        $allSteps = OnboardingStep::all();

        if (count(array_intersect($steps, $allSteps)) >= count($allSteps)) {
            $user->onboarding_completed_at = Carbon::now();
        }

        $user->save();
    }
}
