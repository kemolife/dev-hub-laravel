<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserPostControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_only_authenticated_users_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($other)->draft()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me/posts');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_status(): void
    {
        $user = User::factory()->create();

        Post::factory()->for($user)->draft()->count(2)->create();
        Post::factory()->for($user)->published()->count(1)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me/posts?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
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

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me/posts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
