<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Concerns\RespectsNotificationPreferences;
use App\Enums\NotificationType;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReplyToYourComment extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(public readonly Comment $reply) {}

    protected function notificationType(): NotificationType
    {
        return NotificationType::ReplyToComment;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $replierName = $this->reply->user->name ?? 'Someone';
        $post = $this->reply->commentable;
        $postSlug = $post instanceof Post ? $post->slug : '';

        return (new MailMessage)
            ->subject('Someone replied to your comment')
            ->line("{$replierName} replied to your comment.")
            ->action('View reply', url("/posts/{$postSlug}"));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $post = $this->reply->commentable;
        $postSlug = $post instanceof Post ? $post->slug : null;

        return [
            'type' => NotificationType::ReplyToComment->value,
            'comment_id' => $this->reply->id,
            'post_slug' => $postSlug,
            'replier_name' => $this->reply->user->name,
            'url' => '/posts/'.($postSlug ?? ''),
        ];
    }
}
