<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Ai;

use App\Actions\Ai\ContinueConversationAction;
use App\Data\Ai\ContinueConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\OllamaClient;
use Generator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContinueConversationActionTest extends TestCase
{
    #[Test]
    public function it_appends_user_message_and_passes_prior_history_to_ollama(): void
    {
        $conversation = AiConversation::factory()->create();
        AiMessage::factory()->for($conversation, 'conversation')->create([
            'role' => MessageRole::User,
            'content' => 'What is this?',
        ]);
        AiMessage::factory()->for($conversation, 'conversation')->assistant()->create([
            'content' => 'It is a thing.',
        ]);

        $data = new ContinueConversationData('Can you elaborate?');
        $fakeStream = (function (): Generator {
            yield 'Sure!';
        })();

        $capturedHistory = null;
        $this->mock(OllamaClient::class, function (MockInterface $mock) use ($fakeStream, &$capturedHistory): void {
            $mock->shouldReceive('chat')
                ->once()
                ->withArgs(function (string $prompt, array $history) use (&$capturedHistory): bool {
                    $capturedHistory = $history;

                    return $prompt === 'Can you elaborate?';
                })
                ->andReturn($fakeStream);
        });

        $action = app(ContinueConversationAction::class);
        $stream = $action->execute($conversation, $data);

        // History sent to Ollama contains prior messages only (not the new one)
        $this->assertCount(2, $capturedHistory);
        $this->assertSame('user', $capturedHistory[0]['role']);
        $this->assertSame('What is this?', $capturedHistory[0]['content']);
        $this->assertSame('assistant', $capturedHistory[1]['role']);
        $this->assertSame('It is a thing.', $capturedHistory[1]['content']);

        // New user message was persisted
        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User->value,
            'content' => 'Can you elaborate?',
        ]);

        $this->assertSame($fakeStream, $stream);
    }
}
