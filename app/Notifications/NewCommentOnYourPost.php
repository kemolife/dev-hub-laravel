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

class NewCommentOnYourPost extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(public readonly Comment $comment) {}

    protected function notificationType(): NotificationType
    {
        return NotificationType::NewCommentOnPost;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $commenterName = $this->comment->user->name ?? 'Someone';
        $post = $this->comment->commentable;
        $postSlug = $post instanceof Post ? $post->slug : '';

        return (new MailMessage)
            ->subject('New comment on your post')
            ->line("{$commenterName} commented on your post.")
            ->action('View comment', url("/posts/{$postSlug}"));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $post = $this->comment->commentable;
        $postSlug = $post instanceof Post ? $post->slug : null;

        return [
            'type' => NotificationType::NewCommentOnPost->value,
            'comment_id' => $this->comment->id,
            'post_slug' => $postSlug,
            'commenter_name' => $this->comment->user->name,
            'url' => '/posts/'.($postSlug ?? ''),
        ];
    }
}
