<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;

trait RespectsNotificationPreferences
{
    abstract protected function notificationType(): NotificationType;

    /**
     * Determine which channels to deliver this notification on,
     * respecting the notifiable's stored preferences (or defaults if none set).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        $type = $this->notificationType();
        $defaults = $type->defaultChannels();
        $channels = [];

        foreach ($defaults as $channel) {
            $pref = NotificationPreference::where('user_id', $notifiable->id)
                ->where('type', $type->value)
                ->where('channel', $channel->value)
                ->first();

            $enabled = $pref ? $pref->enabled : true;

            if (! $enabled) {
                continue;
            }

            // If digest mode is on for mail, skip — the scheduler handles that delivery
            if ($channel === NotificationChannel::Mail && $pref?->digest) {
                continue;
            }

            $channels[] = $channel->value;
        }

        return $channels ?: ['database'];
    }
}
