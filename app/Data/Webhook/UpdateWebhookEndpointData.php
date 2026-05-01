<?php

declare(strict_types=1);

namespace App\Data\Webhook;

readonly class UpdateWebhookEndpointData
{
    /**
     * @param  array<int, string>|null  $events
     */
    public function __construct(
        public ?string $url,
        public ?array $events,
        public ?bool $enabled,
    ) {}

    /**
     * @param  array{url?: string, events?: array<int, string>, enabled?: bool}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            url: $data['url'] ?? null,
            events: $data['events'] ?? null,
            enabled: $data['enabled'] ?? null,
        );
    }
}
