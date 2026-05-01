<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TrialEndedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HandleTrialExpiredJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $user) {}

    public function handle(): void
    {
        $this->user->update(['plan' => 'free']);
        $this->user->notify(new TrialEndedNotification);
    }
}
