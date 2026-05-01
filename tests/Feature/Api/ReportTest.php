<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_submit_a_report(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reports/post/{$post->id}", [
                'reason' => 'spam',
                'description' => 'This is clearly spam content.',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'id']);

        $this->assertDatabaseHas('reports', [
            'reporter_user_id' => $user->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);
    }

    public function test_report_requires_reason(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reports/post/{$post->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_report_with_invalid_type_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/reports/invalid_type/1', ['reason' => 'spam'])
            ->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_submit_report(): void
    {
        $post = Post::factory()->create();

        $this->postJson("/api/v1/reports/post/{$post->id}", ['reason' => 'spam'])
            ->assertUnauthorized();
    }

    public function test_rate_limit_blocks_excessive_reports(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/reports/post/{$post->id}", ['reason' => 'spam'])
                ->assertCreated();
        }

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/reports/post/{$post->id}", ['reason' => 'spam'])
            ->assertStatus(429);
    }
}
