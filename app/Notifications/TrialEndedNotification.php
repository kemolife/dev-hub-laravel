<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndedNotification extends Notification implements ShouldQueue
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
            ->subject('Your DevHub trial has ended')
            ->line('Your free Pro trial has ended. Your account has been moved to the Free plan.')
            ->line('You can upgrade at any time to restore Pro features.')
            ->action('Upgrade to Pro', route('billing.checkout'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
