<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewFollowerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $follower) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_follower',
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'follower_username' => $this->follower->username,
        ];
    }
}
