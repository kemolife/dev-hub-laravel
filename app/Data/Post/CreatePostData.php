<?php

declare(strict_types=1);

namespace App\Data\Post;

readonly class CreatePostData
{
    public function __construct(
        public string $title,
        public ?string $excerpt = null,
        public ?string $bodyMarkdown = null,
    ) {}

    /** @param array{title: string, excerpt?: ?string, body_markdown?: ?string} $data */
    public static function from(array $data): self
    {
        return new self(
            title: $data['title'],
            excerpt: $data['excerpt'] ?? null,
            bodyMarkdown: $data['body_markdown'] ?? null,
        );
    }
}
