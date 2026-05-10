<?php

declare(strict_types=1);

namespace App\Data\Ai;

readonly class StartConversationData
{
    public function __construct(
        public string $selectedText,
        public int $selectionStart,
        public int $selectionEnd,
    ) {}

    /** @param array{selected_text: string, selection_start: int, selection_end: int} $data */
    public static function from(array $data): self
    {
        return new self(
            selectedText: $data['selected_text'],
            selectionStart: $data['selection_start'],
            selectionEnd: $data['selection_end'],
        );
    }
}
