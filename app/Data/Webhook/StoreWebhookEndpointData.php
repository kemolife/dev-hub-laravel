<?php

declare(strict_types=1);

namespace App\Data\Webhook;

readonly class StoreWebhookEndpointData
{
    /**
     * @param  array<int, string>  $events
     */
    public function __construct(
        public string $url,
        public array $events,
    ) {}

    /**
     * @param  array{url: string, events: array<int, string>}  $data
     */
    public static function from(array $data): self
    {
        return new self(
            url: $data['url'],
            events: $data['events'],
        );
    }
}
