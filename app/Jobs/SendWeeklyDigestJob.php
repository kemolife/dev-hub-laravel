<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\WeeklyDigestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWeeklyDigestJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        User::whereHas('unreadNotifications')
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    $notifications = $user->unreadNotifications()
                        ->where('created_at', '>=', now()->subWeek())
                        ->get();

                    if ($notifications->isEmpty()) {
                        continue;
                    }

                    $pref = NotificationPreference::where('user_id', $user->id)
                        ->where('type', NotificationType::WeeklyDigest->value)
                        ->where('channel', NotificationChannel::Mail->value)
                        ->first();

                    if ($pref !== null && ! $pref->enabled) {
                        continue;
                    }

                    $user->notify(new WeeklyDigestNotification($notifications));
                }
            });
    }
}
