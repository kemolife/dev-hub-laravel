<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [1, 5, 10, 30, 60];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly WebhookEndpoint $endpoint,
        public readonly string $event,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $signature = hash_hmac('sha256', json_encode($this->payload) ?: '', $this->endpoint->secret);

        $delivery = $this->endpoint->deliveries()->create([
            'event' => $this->event,
            'payload' => $this->payload,
            'attempt_count' => $this->attempts(),
        ]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-DevHub-Signature' => 'sha256='.$signature,
                    'X-DevHub-Event' => $this->event,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->endpoint->url, $this->payload);

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 1000),
                'delivered_at' => now(),
            ]);

            $this->endpoint->update([
                'last_success_at' => now(),
                'failure_count' => 0,
            ]);
        } catch (\Exception $e) {
            $delivery->update(['failed_at' => now(), 'response_body' => $e->getMessage()]);
            $this->endpoint->increment('failure_count');

            if ($this->endpoint->fresh()?->failure_count >= 10) {
                $this->endpoint->update(['enabled' => false]);

                return;
            }

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->endpoint->update(['last_failure_at' => now()]);
    }
}
