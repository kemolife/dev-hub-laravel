<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Ai;

use App\Actions\Ai\StartConversationAction;
use App\Data\Ai\StartConversationData;
use App\Enums\MessageRole;
use App\Models\AiConversation;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartConversationActionTest extends TestCase
{
    #[Test]
    public function it_creates_conversation_and_user_message_then_returns_stream(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $data = new StartConversationData('some text', 10, 20);

        $fakeStream = (function (): Generator {
            yield 'Hello';
            yield ' world';
        })();

        $this->mock(OllamaClient::class, function (MockInterface $mock) use ($fakeStream): void {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn($fakeStream);
        });

        $action = app(StartConversationAction::class);
        [$conversation, $stream] = $action->execute($user, $post, $data);

        $this->assertInstanceOf(AiConversation::class, $conversation);
        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'selected_text' => 'some text',
            'selection_start' => 10,
            'selection_end' => 20,
            'is_private' => false,
        ]);
        $this->assertDatabaseHas('ai_messages', [
            'conversation_id' => $conversation->id,
            'role' => MessageRole::User->value,
            'content' => 'some text',
        ]);
        $this->assertSame($fakeStream, $stream);
    }
}
