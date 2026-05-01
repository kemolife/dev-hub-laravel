<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_list_comments_on_a_published_post(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->forPost($post)->count(3)->create();

        $response = $this->getJson("/api/v1/posts/{$post->slug}/comments");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_post_a_comment(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'body_markdown' => 'Great post!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.body_markdown', 'Great post!')
            ->assertJsonPath('data.is_deleted', false);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        Event::assertDispatched(CommentPosted::class);
    }

    public function test_user_can_reply_to_a_comment(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $parent = Comment::factory()->forPost($post)->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'body_markdown' => 'A reply!',
            'parent_id' => $parent->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.body_markdown', 'A reply!');

        $this->assertDatabaseHas('comments', [
            'parent_id' => $parent->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_max_nesting_depth_4_is_enforced(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        Sanctum::actingAs($user, ['*']);

        // Build chain: depth 1 → 2 → 3 → 4
        $depth1 = Comment::factory()->forPost($post)->create();
        $depth2 = Comment::factory()->forPost($post)->withParent($depth1)->create();
        $depth3 = Comment::factory()->forPost($post)->withParent($depth2)->create();
        $depth4 = Comment::factory()->forPost($post)->withParent($depth3)->create();

        // Attempt to reply to depth4 (would create depth5)
        $response = $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'body_markdown' => 'Too deep!',
            'parent_id' => $depth4->id,
        ]);

        $response->assertUnprocessable();
    }

    public function test_owner_can_edit_their_comment_within_15_minutes(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(5),
        ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/posts/{$post->slug}/comments/{$comment->id}", [
            'body_markdown' => 'Edited content.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.body_markdown', 'Edited content.');

        $this->assertNotNull($response->json('data.edited_at'));
    }

    public function test_edit_fails_after_15_minute_window(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(20),
        ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/posts/{$post->slug}/comments/{$comment->id}", [
            'body_markdown' => 'Late edit.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('body_markdown');
    }

    public function test_deleting_comment_with_replies_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create(['user_id' => $user->id]);
        Comment::factory()->forPost($post)->withParent($comment)->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/posts/{$post->slug}/comments/{$comment->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_deleting_comment_without_replies_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/posts/{$post->slug}/comments/{$comment->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_other_user_cannot_edit_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create([
            'user_id' => $owner->id,
            'created_at' => now()->subMinutes(5),
        ]);
        Sanctum::actingAs($other, ['*']);

        $response = $this->putJson("/api/v1/posts/{$post->slug}/comments/{$comment->id}", [
            'body_markdown' => 'Hijacked!',
        ]);

        $response->assertForbidden();
    }

    public function test_other_user_cannot_delete_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create(['user_id' => $owner->id]);
        Sanctum::actingAs($other, ['*']);

        $response = $this->deleteJson("/api/v1/posts/{$post->slug}/comments/{$comment->id}");

        $response->assertForbidden();
    }

    public function test_deleted_comment_shows_tombstone_in_list(): void
    {
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->forPost($post)->create();
        $comment->delete();

        $response = $this->getJson("/api/v1/posts/{$post->slug}/comments");

        $response->assertOk()
            ->assertJsonPath('data.0.is_deleted', true)
            ->assertJsonPath('data.0.body_html', '<p>[deleted]</p>')
            ->assertJsonPath('data.0.author', null);
    }
}
