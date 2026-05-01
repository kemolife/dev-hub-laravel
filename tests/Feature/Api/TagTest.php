<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TagTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_tags_list_returns_popular_tags_ordered_by_posts_count(): void
    {
        Tag::factory()->create(['name' => 'php', 'slug' => 'php', 'posts_count' => 10]);
        Tag::factory()->create(['name' => 'laravel', 'slug' => 'laravel', 'posts_count' => 50]);
        Tag::factory()->create(['name' => 'javascript', 'slug' => 'javascript', 'posts_count' => 5]);

        $response = $this->getJson('/api/v1/tags');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'laravel')
            ->assertJsonPath('data.1.slug', 'php')
            ->assertJsonPath('data.2.slug', 'javascript');
    }

    public function test_individual_tag_can_be_retrieved_by_slug(): void
    {
        $tag = Tag::factory()->create(['name' => 'laravel', 'slug' => 'laravel']);

        $response = $this->getJson("/api/v1/tags/{$tag->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', 'laravel')
            ->assertJsonPath('data.name', 'laravel');
    }

    public function test_tags_are_attached_to_post_when_creating(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Post with Tags',
            'tags' => ['Laravel', 'PHP'],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('tags', ['slug' => 'laravel']);
        $this->assertDatabaseHas('tags', ['slug' => 'php']);
    }

    public function test_tags_are_synced_when_updating_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['*']);

        $this->putJson("/api/v1/posts/{$post->slug}", [
            'tags' => ['vue', 'react'],
        ])->assertOk()
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('tags', ['slug' => 'vue']);
        $this->assertDatabaseHas('tags', ['slug' => 'react']);
    }

    public function test_tags_are_normalized_on_creation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/posts', [
            'title' => 'Post with Unnormalized Tags',
            'tags' => ['React.js', 'Node JS'],
        ])->assertCreated();

        $this->assertDatabaseHas('tags', ['slug' => 'reactjs']);
        $this->assertDatabaseHas('tags', ['slug' => 'node-js']);
    }

    public function test_maximum_five_tags_are_allowed_per_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/posts', [
            'title' => 'Many Tags Post',
            'tags' => ['one', 'two', 'three', 'four', 'five', 'six'],
        ])->assertUnprocessable();
    }
}
