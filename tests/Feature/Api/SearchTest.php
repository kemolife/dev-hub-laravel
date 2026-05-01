<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_empty_query_returns_recent_published_posts(): void
    {
        Post::factory()->published()->count(3)->create();
        Post::factory()->draft()->count(2)->create();

        $response = $this->getJson('/api/v1/search');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_empty_query_returns_posts_ordered_by_published_at_descending(): void
    {
        $older = Post::factory()->published()->create(['published_at' => now()->subDays(10)]);
        $newer = Post::factory()->published()->create(['published_at' => now()->subDay()]);

        $response = $this->getJson('/api/v1/search');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertEquals($newer->public_id, $ids->first());
        $this->assertEquals($older->public_id, $ids->last());
    }

    public function test_search_endpoint_returns_paginated_structure(): void
    {
        $response = $this->getJson('/api/v1/search?q=laravel');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_search_query_is_recorded_when_q_param_is_present(): void
    {
        $this->getJson('/api/v1/search?q=laravel+tips');

        $this->assertDatabaseHas('search_queries', [
            'query' => 'laravel tips',
        ]);
    }

    public function test_search_query_is_recorded_with_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/search?q=php');

        $this->assertDatabaseHas('search_queries', [
            'query' => 'php',
            'user_id' => $user->id,
        ]);
    }

    public function test_empty_query_does_not_create_a_search_query_record(): void
    {
        $this->getJson('/api/v1/search');

        $this->assertDatabaseCount('search_queries', 0);
    }

    public function test_search_validates_q_param_length(): void
    {
        $response = $this->getJson('/api/v1/search?q='.str_repeat('a', 201));

        $response->assertUnprocessable();
    }

    public function test_search_validates_sort_param_to_allowed_values(): void
    {
        $response = $this->getJson('/api/v1/search?sort=invalid');

        $response->assertUnprocessable();
    }
}
