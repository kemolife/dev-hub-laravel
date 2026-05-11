<?php

declare(strict_types=1);

namespace App\Data\Ai;

readonly class ContinueConversationData
{
    public function __construct(
        public string $content,
    ) {}

    /** @param array{content: string} $data */
    public static function from(array $data): self
    {
        return new self(content: $data['content']);
    }
}
