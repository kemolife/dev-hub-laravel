<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case NewCommentOnPost = 'new_comment_on_post';
    case ReplyToComment = 'reply_to_comment';
    case MentionedInComment = 'mentioned_in_comment';
    case NewFollower = 'new_follower';
    case WeeklyDigest = 'weekly_digest';

    /** @return array<int, NotificationChannel> */
    public function defaultChannels(): array
    {
        return match ($this) {
            self::NewCommentOnPost => [NotificationChannel::Database, NotificationChannel::Mail],
            self::ReplyToComment => [NotificationChannel::Database, NotificationChannel::Mail],
            self::MentionedInComment => [NotificationChannel::Database, NotificationChannel::Mail],
            self::NewFollower => [NotificationChannel::Database],
            self::WeeklyDigest => [NotificationChannel::Mail],
        };
    }
}
