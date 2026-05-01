<?php

declare(strict_types=1);

namespace App\Data\Post;

use App\Enums\PostStatus;

readonly class UpdatePostData
{
    /**
     * @param  array<string>|null  $tags
     */
    public function __construct(
        public ?string $title = null,
        public ?string $excerpt = null,
        public ?string $bodyMarkdown = null,
        public ?PostStatus $status = null,
        public ?array $tags = null,
    ) {}

    /** @param array{title?: ?string, excerpt?: ?string, body_markdown?: ?string, status?: ?string, tags?: array<string>|null} $data */
    public static function from(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            excerpt: $data['excerpt'] ?? null,
            bodyMarkdown: $data['body_markdown'] ?? null,
            status: isset($data['status']) ? PostStatus::from($data['status']) : null,
            tags: $data['tags'] ?? null,
        );
    }
}
