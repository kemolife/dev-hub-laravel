<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * @implements CastsAttributes<string, string>
 */
class MarkdownCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [
                'body_markdown' => null,
                'body_html' => null,
                'reading_time_seconds' => null,
            ];
        }

        $converter = new GithubFlavoredMarkdownConverter;
        $html = (string) $converter->convert($value);

        $wordCount = str_word_count(strip_tags($html));
        $seconds = (int) ceil($wordCount / 200 * 60);

        return [
            'body_markdown' => $value,
            'body_html' => $html,
            'reading_time_seconds' => $seconds,
        ];
    }
}
