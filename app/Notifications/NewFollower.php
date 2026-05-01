<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Concerns\RespectsNotificationPreferences;
use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFollower extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(public readonly User $follower) {}

    protected function notificationType(): NotificationType
    {
        return NotificationType::NewFollower;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New follower')
            ->line("{$this->follower->name} is now following you.");
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationType::NewFollower->value,
            'follower_name' => $this->follower->name,
        ];
    }
}
