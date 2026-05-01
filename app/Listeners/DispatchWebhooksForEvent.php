<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Events\PostPublished;
use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookEndpoint;

class DispatchWebhooksForEvent
{
    public function handle(object $event): void
    {
        $eventName = match (true) {
            $event instanceof PostPublished => 'post.published',
            $event instanceof CommentPosted => 'comment.posted',
            default => null,
        };

        if (! $eventName) {
            return;
        }

        $payload = $this->buildPayload($event, $eventName);

        WebhookEndpoint::where('enabled', true)
            ->whereJsonContains('events', $eventName)
            ->chunk(100, function ($endpoints) use ($eventName, $payload): void {
                foreach ($endpoints as $endpoint) {
                    dispatch(new DeliverWebhookJob($endpoint, $eventName, $payload));
                }
            });
    }

    /** @return array<string, mixed> */
    private function buildPayload(object $event, string $eventName): array
    {
        return [
            'event' => $eventName,
            'timestamp' => now()->toISOString(),
            'data' => [],
        ];
    }
}
