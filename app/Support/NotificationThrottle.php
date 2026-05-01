<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Rate-limits notifications per user per type to protect against spam bursts.
 */
final class NotificationThrottle
{
    /**
     * Attempt to allow a notification through the throttle.
     *
     * Returns true if under the hourly limit and increments the counter.
     * Returns false if the limit has been reached.
     */
    public static function allow(User $user, NotificationType $type, int $maxPerHour = 10): bool
    {
        $key = "notif_throttle:{$user->id}:{$type->value}";
        $count = Cache::get($key, 0);

        if ($count >= $maxPerHour) {
            return false;
        }

        Cache::put($key, $count + 1, 3600);

        return true;
    }
}
