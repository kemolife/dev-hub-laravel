<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\DeliverWebhookJob;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_create_a_webhook_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['post.published'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.url', 'https://example.com/webhook')
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.events', ['post.published']);

        $this->assertDatabaseHas('webhook_endpoints', [
            'user_id' => $user->id,
            'url' => 'https://example.com/webhook',
        ]);
    }

    public function test_user_can_list_their_webhook_endpoints(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        WebhookEndpoint::factory()->count(2)->create(['user_id' => $user->id]);
        WebhookEndpoint::factory()->count(3)->create(['user_id' => $other->id]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/webhooks')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_webhook_creation_validates_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/webhooks', [
            'url' => 'not-a-url',
            'events' => ['post.published'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_webhook_creation_validates_events_are_known(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/hook',
            'events' => ['unknown.event'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['events.0']);
    }

    public function test_user_can_update_their_webhook_endpoint(): void
    {
        $user = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->putJson("/api/v1/webhooks/{$endpoint->id}", [
            'enabled' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_user_cannot_update_another_users_webhook_endpoint(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($user, ['*']);

        $this->putJson("/api/v1/webhooks/{$endpoint->id}", [
            'enabled' => false,
        ])->assertForbidden();
    }

    public function test_user_can_delete_their_webhook_endpoint(): void
    {
        $user = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->deleteJson("/api/v1/webhooks/{$endpoint->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('webhook_endpoints', ['id' => $endpoint->id]);
    }

    public function test_user_cannot_delete_another_users_webhook_endpoint(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($user, ['*']);

        $this->deleteJson("/api/v1/webhooks/{$endpoint->id}")
            ->assertForbidden();
    }

    public function test_test_endpoint_dispatches_deliver_webhook_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/webhooks/{$endpoint->id}/test")
            ->assertOk();

        Bus::assertDispatched(DeliverWebhookJob::class, function (DeliverWebhookJob $job) use ($endpoint): bool {
            return $job->endpoint->id === $endpoint->id && $job->event === 'ping';
        });
    }

    public function test_idempotency_returns_cached_response_on_duplicate_key(): void
    {
        // The database cache driver persists across HTTP test requests (array driver does not).
        // We must also flush the resolved cache manager so it picks up the new default.
        config(['cache.default' => 'database']);
        $this->app->forgetInstance('cache');
        $this->app->forgetInstance('cache.store');

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $idempotencyKey = 'unique-key-'.uniqid();

        $firstResponse = $this->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/hook',
                'events' => ['post.published'],
            ])
            ->assertCreated();

        $secondResponse = $this->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/webhooks', [
                'url' => 'https://example.com/different-url',
                'events' => ['comment.posted'],
            ])
            ->assertCreated()
            ->assertHeader('X-Idempotent-Replayed', 'true');

        $this->assertSame(
            $firstResponse->json('data.url'),
            $secondResponse->json('data.url')
        );
    }

    public function test_webhook_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/webhooks')->assertUnauthorized();
        $this->postJson('/api/v1/webhooks', [])->assertUnauthorized();
        $this->putJson('/api/v1/webhooks/1', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/webhooks/1')->assertUnauthorized();
    }
}
