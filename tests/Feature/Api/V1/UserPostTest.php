<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPostTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_authenticated_users_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($other)->draft()->count(3)->create();

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_draft_status(): void
    {
        $user = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($user)->published()->count(1)->create();

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me/posts?status=draft')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_published_status(): void
    {
        $user = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($user)->published()->count(1)->create();

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me/posts?status=published')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/me/posts')
            ->assertUnauthorized();
    }

    public function test_does_not_return_other_users_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->for($other)->draft()->count(5)->create();

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me/posts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_response_has_paginated_structure(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->draft()->create();

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me/posts')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug', 'tags']],
                'links',
                'meta',
            ]);
    }

    public function test_orders_by_updated_at_descending(): void
    {
        $user = User::factory()->create();

        $older = Post::factory()->for($user)->draft()->create([
            'updated_at' => now()->subHour(),
        ]);
        $newer = Post::factory()->for($user)->draft()->create([
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me/posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->public_id)
            ->assertJsonPath('data.1.id', $older->public_id);
    }

    public function test_invalid_status_value_returns_all_posts(): void
    {
        $user = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($user)->published()->count(1)->create();

        Sanctum::actingAs($user, ['*']);

        // Invalid enum value silently ignored — returns all posts
        $this->getJson('/api/v1/me/posts?status=garbage')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
