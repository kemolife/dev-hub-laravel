<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\PostPublished;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_list_published_posts(): void
    {
        Post::factory()->published()->count(3)->create();
        Post::factory()->draft()->count(2)->create();

        $response = $this->getJson('/api/v1/posts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_can_view_published_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', $post->slug)
            ->assertJsonPath('data.id', $post->public_id);
    }

    public function test_authenticated_user_can_create_a_draft_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'My First Post',
            'excerpt' => 'A short summary.',
            'body_markdown' => '# Hello World',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My First Post')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('posts', [
            'title' => 'My First Post',
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_update_their_draft_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/posts/{$post->slug}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_authenticated_user_cannot_update_another_users_post(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($otherUser, ['*']);

        $this->putJson("/api/v1/posts/{$post->slug}", [
            'title' => 'Stolen Title',
        ])->assertForbidden();
    }

    public function test_owner_can_publish_their_draft_post_and_event_is_fired(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->draft()->create([
            'user_id' => $user->id,
            'title' => 'Ready to Publish',
            'body_markdown' => '# Content here',
        ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/posts/{$post->slug}/publish");

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');

        Event::assertDispatched(PostPublished::class, fn (PostPublished $event) => $event->post->id === $post->id);
    }

    public function test_slug_is_stable_when_published_post_title_is_updated(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Original Title']);
        $originalSlug = $post->slug;
        Sanctum::actingAs($user, ['*']);

        $this->putJson("/api/v1/posts/{$post->slug}", [
            'title' => 'New Title Attempt',
        ])->assertOk();

        $this->assertEquals($originalSlug, $post->fresh()->slug);
    }

    public function test_soft_deleted_post_not_visible_in_listing(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
