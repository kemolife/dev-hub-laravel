<?php

declare(strict_types=1);

namespace App\Support;

readonly class MentionParser
{
    /**
     * Extract unique @usernames from text, excluding email addresses.
     *
     * @return array<string>
     */
    public static function extract(string $text): array
    {
        // Match @username but not email patterns (not preceded by word chars or @)
        preg_match_all('/(?<![a-zA-Z0-9@])@([a-zA-Z0-9_]{1,50})/', $text, $matches);

        return array_unique($matches[1]);
    }
}
