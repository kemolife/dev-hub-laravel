<?php

declare(strict_types=1);

namespace App\Data\Feedback;

readonly class SubmitFeedbackData
{
    public function __construct(
        public string $type,
        public string $description,
        public ?string $email = null,
        public ?string $url = null,
    ) {}

    /**
     * @param  array{type: string, description: string, email?: string|null, url?: string|null}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            type: $data['type'],
            description: $data['description'],
            email: $data['email'] ?? null,
            url: $data['url'] ?? null,
        );
    }
}
