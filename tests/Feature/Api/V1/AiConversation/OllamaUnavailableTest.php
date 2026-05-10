<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Exceptions\OllamaUnavailableException;
use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OllamaUnavailableTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_returns_503_when_ollama_is_unavailable(): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chat')->andThrow(new OllamaUnavailableException('Connection refused'));
        });

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'some text',
                'selection_start' => 0,
                'selection_end' => 9,
            ])
            ->assertServiceUnavailable()
            ->assertJson(['message' => 'AI service is currently unavailable. Please try again later.']);
    }
}
