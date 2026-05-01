<?php

declare(strict_types=1);

namespace App\Enums;

enum OnboardingStep: string
{
    case ProfileCompleted = 'profile_completed';
    case FirstPostPublished = 'first_post_published';
    case FirstCommentLeft = 'first_comment_left';
    case NotificationPrefsSet = 'notification_prefs_set';

    /** @return array<string> */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
