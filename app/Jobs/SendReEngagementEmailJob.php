<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ReEngagementMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendReEngagementEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $user) {}

    public function handle(): void
    {
        $lastSeenAt = $this->user->last_seen_at;

        if ($lastSeenAt instanceof Carbon && $lastSeenAt->isAfter(now()->subDays(14))) {
            return;
        }

        Mail::to($this->user)->send(new ReEngagementMail($this->user));
    }
}
