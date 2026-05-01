<?php

declare(strict_types=1);

namespace App\Data\Comment;

readonly class EditCommentData
{
    public function __construct(public string $bodyMarkdown) {}

    /**
     * @param  array{body_markdown: string}  $data
     */
    public static function from(array $data): self
    {
        return new self(bodyMarkdown: $data['body_markdown']);
    }
}
