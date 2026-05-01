<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliverWebhookJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hmac_signature_is_correct(): void
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://example.com/hook',
            'secret' => 'test-secret',
        ]);

        $payload = ['event' => 'post.published', 'data' => []];

        Http::fake(['https://example.com/hook' => Http::response('OK', 200)]);

        $job = new DeliverWebhookJob($endpoint, 'post.published', $payload);
        $job->handle();

        Http::assertSent(function (Request $request) use ($payload): bool {
            $expectedSignature = 'sha256='.hash_hmac('sha256', json_encode($payload) ?: '', 'test-secret');

            return $request->header('X-DevHub-Signature')[0] === $expectedSignature;
        });
    }

    public function test_delivery_record_is_created(): void
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://example.com/hook',
            'secret' => 'secret',
        ]);

        $payload = ['event' => 'post.published', 'data' => []];

        Http::fake(['https://example.com/hook' => Http::response('OK', 200)]);

        $job = new DeliverWebhookJob($endpoint, 'post.published', $payload);
        $job->handle();

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_endpoint_id' => $endpoint->id,
            'event' => 'post.published',
            'response_status' => 200,
        ]);
    }

    public function test_failed_endpoint_disables_after_10_failures(): void
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://example.com/hook',
            'secret' => 'secret',
            'failure_count' => 9,
            'enabled' => true,
        ]);

        // Simulate a connection error (throws exception, not just 5xx response)
        Http::fake(['https://example.com/hook' => function (): never {
            throw new ConnectionException('Connection refused');
        }]);

        $job = new DeliverWebhookJob($endpoint, 'post.published', ['event' => 'post.published']);

        try {
            $job->handle();
        } catch (\Exception) {
            // job re-throws after incrementing failure_count to 10
        }

        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $endpoint->id,
            'enabled' => false,
        ]);
    }

    public function test_x_devhub_event_header_is_sent(): void
    {
        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://example.com/hook',
            'secret' => 'secret',
        ]);

        Http::fake(['https://example.com/hook' => Http::response('OK', 200)]);

        $job = new DeliverWebhookJob($endpoint, 'comment.posted', ['event' => 'comment.posted']);
        $job->handle();

        Http::assertSent(function (Request $request): bool {
            return $request->header('X-DevHub-Event')[0] === 'comment.posted';
        });
    }
}
