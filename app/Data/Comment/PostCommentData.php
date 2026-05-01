<?php

declare(strict_types=1);

namespace App\Data\Comment;

readonly class PostCommentData
{
    public function __construct(
        public string $bodyMarkdown,
        public ?int $parentId = null,
    ) {}

    /**
     * @param  array{body_markdown: string, parent_id?: int|null}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            bodyMarkdown: $data['body_markdown'],
            parentId: $data['parent_id'] ?? null,
        );
    }
}
