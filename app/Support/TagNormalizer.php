<?php

declare(strict_types=1);

namespace App\Support;

readonly class TagNormalizer
{
    public static function normalize(string $tag): string
    {
        $tag = mb_strtolower($tag);
        $tag = preg_replace('/[^a-z0-9\s-]/', '', $tag) ?? $tag;
        $tag = preg_replace('/[\s]+/', '-', trim($tag)) ?? $tag;
        $tag = preg_replace('/-+/', '-', $tag) ?? $tag;

        return substr($tag, 0, 50);
    }
}
