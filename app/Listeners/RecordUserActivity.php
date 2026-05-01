<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserActivityRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordUserActivity implements ShouldQueue
{
    public function handle(UserActivityRecorded $event): void
    {
        $event->user->forceFill(['last_seen_at' => now()])->saveQuietly();
    }
}
