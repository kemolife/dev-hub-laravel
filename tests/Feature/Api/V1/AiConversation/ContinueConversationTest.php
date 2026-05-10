<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContinueConversationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function owner_can_add_message_and_gets_streamed_reply(): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chat')->andReturn(
                (function (): Generator {
                    yield 'Follow-up reply';
                })()
            );
        });

        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->public_id}/messages", [
                'content' => 'Can you elaborate?',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');

        $body = $response->getContent();
        $this->assertStringContainsString('"content":"Follow-up reply"', $body);

        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User->value,
            'content' => 'Can you elaborate?',
        ]);
        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Assistant->value,
            'content' => 'Follow-up reply',
        ]);
    }

    #[Test]
    public function non_owner_cannot_add_message(): void
    {
        $conversation = AiConversation::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->public_id}/messages", [
                'content' => 'Can you elaborate?',
            ])
            ->assertForbidden();
    }
}
