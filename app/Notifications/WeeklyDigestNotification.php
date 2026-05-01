<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     */
    public function __construct(public readonly Collection $notifications) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->notifications->count();
        $message = (new MailMessage)
            ->subject("Your DevHub weekly digest ({$count} update".($count !== 1 ? 's' : '').')')
            ->line("You have {$count} unread notification".($count !== 1 ? 's' : '').' from the past week.');

        foreach ($this->notifications->take(10) as $notification) {
            $data = $notification->data;
            $line = $data['type'] ?? 'notification';

            if (isset($data['url'])) {
                $message->action($line, url($data['url']));
            } else {
                $message->line($line);
            }
        }

        if ($count > 10) {
            $message->line('...and '.($count - 10).' more.');
        }

        return $message;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
