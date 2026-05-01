<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\User;
use Illuminate\Support\Carbon;

class StartTrialAction
{
    private const int TRIAL_DAYS = 14;

    public function execute(User $user): void
    {
        $user->update([
            'trial_ends_at' => Carbon::now()->addDays(self::TRIAL_DAYS),
        ]);
    }
}
