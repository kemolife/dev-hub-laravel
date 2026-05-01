<?php

declare(strict_types=1);

use App\Events\TrialEnded;
use App\Jobs\HandleTrialExpiredJob;
use App\Models\User;
use App\Notifications\TrialEndingNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send 2-day trial ending reminders
Schedule::call(function (): void {
    User::whereNotNull('trial_ends_at')
        ->whereBetween('trial_ends_at', [now()->addDays(2)->startOfDay(), now()->addDays(2)->endOfDay()])
        ->each(fn (User $user) => $user->notify(new TrialEndingNotification));
})->daily()->name('trial-ending-reminders');

// Downgrade users whose trial ended yesterday
Schedule::call(function (): void {
    User::whereNotNull('trial_ends_at')
        ->whereBetween('trial_ends_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])
        ->where('plan', 'free')
        ->each(function (User $user): void {
            TrialEnded::dispatch($user);
            HandleTrialExpiredJob::dispatch($user);
        });
})->daily()->name('trial-expired-downgrade');

// Send re-engagement emails to inactive users
Schedule::command('users:send-reengagement')->daily();
