<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Enums\MessageRole;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartConversationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function mockOllama(array $chunks = ['Hello ', 'world']): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock) use ($chunks): void {
            $mock->shouldReceive('chat')->andReturn(
                (function () use ($chunks): Generator {
                    foreach ($chunks as $c) {
                        yield $c;
                    }
                })()
            );
        });
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/conversations", [
            'selected_text' => 'some text',
            'selection_start' => 0,
            'selection_end' => 9,
        ])->assertUnauthorized();
    }

    #[Test]
    public function it_creates_conversation_and_streams_response(): void
    {
        $this->mockOllama(['Hello ', 'world']);
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'some text',
                'selection_start' => 0,
                'selection_end' => 9,
            ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'selected_text' => 'some text',
        ]);
        $this->assertDatabaseHas('ai_messages', ['role' => MessageRole::User->value, 'content' => 'some text']);
        $this->assertDatabaseHas('ai_messages', ['role' => MessageRole::Assistant->value, 'content' => 'Hello world']);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selected_text', 'selection_start', 'selection_end']);
    }

    #[Test]
    public function it_validates_selection_end_greater_than_start(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'text',
                'selection_start' => 50,
                'selection_end' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selection_end']);
    }
}
