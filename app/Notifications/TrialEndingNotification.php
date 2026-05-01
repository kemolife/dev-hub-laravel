<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your DevHub trial ends in 2 days')
            ->line('Your free Pro trial ends in 2 days.')
            ->line('Upgrade now to keep unlimited posts, analytics, and API access.')
            ->action('Upgrade to Pro', route('billing.checkout'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
