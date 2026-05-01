<?php

declare(strict_types=1);

namespace App\Actions\Notification;

use App\Data\Notification\UpdatePreferencesData;
use App\Models\NotificationPreference;
use App\Models\User;

class UpsertNotificationPreferencesAction
{
    /**
     * @param  array<int, UpdatePreferencesData>  $preferences
     */
    public function execute(User $user, array $preferences): void
    {
        foreach ($preferences as $preference) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $preference->type->value,
                    'channel' => $preference->channel->value,
                ],
                [
                    'enabled' => $preference->enabled,
                    'digest' => $preference->digest,
                ],
            );
        }
    }
}
