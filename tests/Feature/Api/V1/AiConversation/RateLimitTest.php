<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Models\Post;
use App\Models\User;
use App\Services\OllamaClient;
use Generator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
    }

    #[Test]
    public function it_rate_limits_after_20_requests_per_minute(): void
    {
        $this->mock(OllamaClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chat')->andReturn(
                (function (): Generator {
                    yield 'ok';
                })()
            );
        });

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                    'selected_text' => 'some text',
                    'selection_start' => 0,
                    'selection_end' => 9,
                ])
                ->assertStatus(200);
        }

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/posts/{$post->slug}/conversations", [
                'selected_text' => 'some text',
                'selection_start' => 0,
                'selection_end' => 9,
            ])
            ->assertTooManyRequests();
    }
}
