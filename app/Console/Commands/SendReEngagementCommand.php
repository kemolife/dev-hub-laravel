<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendReEngagementEmailJob;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:send-reengagement')]
#[Description('Dispatch re-engagement emails for users inactive for 14+ days')]
class SendReEngagementCommand extends Command
{
    public function handle(): int
    {
        $count = 0;

        User::query()
            ->where(function ($query): void {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<=', now()->subDays(14));
            })
            ->each(function (User $user) use (&$count): void {
                SendReEngagementEmailJob::dispatch($user);
                $count++;
            });

        $this->info("Dispatched re-engagement emails for {$count} users.");

        return self::SUCCESS;
    }
}
