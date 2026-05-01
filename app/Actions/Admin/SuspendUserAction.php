<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use DateTimeInterface;

readonly class SuspendUserAction
{
    public function execute(User $target, User $actor, string $reason, ?DateTimeInterface $until = null): void
    {
        $target->update([
            'suspended_at' => now(),
            'suspended_until' => $until,
            'suspension_reason' => $reason,
        ]);

        $target->logActivity('user.suspended', [], ['reason' => $reason], $actor);
    }
}
