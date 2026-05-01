<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ReactionType;
use App\Events\ReactionToggled;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_toggle_reaction_on_post(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/posts/{$post->slug}/reactions", [
            'type' => 'like',
        ]);

        $response->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('reactions_count', 1);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => ReactionType::Like->value,
        ]);

        Event::assertDispatched(ReactionToggled::class);
    }

    public function test_toggling_existing_reaction_removes_it(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        Sanctum::actingAs($user, ['*']);

        // Add reaction
        $this->postJson("/api/v1/posts/{$post->slug}/reactions", ['type' => 'like'])
            ->assertOk()
            ->assertJsonPath('added', true);

        // Remove reaction - event not dispatched on removal
        $response = $this->postJson("/api/v1/posts/{$post->slug}/reactions", ['type' => 'like']);

        $response->assertOk()
            ->assertJsonPath('added', false)
            ->assertJsonPath('reactions_count', 0);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => ReactionType::Like->value,
        ]);
    }

    public function test_guest_cannot_react_to_a_post(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/reactions", ['type' => 'like'])
            ->assertUnauthorized();
    }

    public function test_reaction_with_invalid_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/posts/{$post->slug}/reactions", ['type' => 'invalid_type'])
            ->assertUnprocessable();
    }

    public function test_different_reaction_types_can_coexist_on_same_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/posts/{$post->slug}/reactions", ['type' => 'like'])
            ->assertOk()
            ->assertJsonPath('added', true);

        $this->postJson("/api/v1/posts/{$post->slug}/reactions", ['type' => 'fire'])
            ->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('reactions_count', 2);
    }
}
