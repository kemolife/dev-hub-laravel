<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonMissingPath('data.password');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_me_exposes_public_id_not_integer_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->public_id);
    }

    public function test_last_seen_at_updated_on_authenticated_request(): void
    {
        $user = User::factory()->create(['last_seen_at' => null]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/me')->assertOk();

        $this->assertNotNull($user->fresh()->last_seen_at);
    }

    public function test_guest_can_view_public_user_profile(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/v1/users/{$user->username}")
            ->assertOk()
            ->assertJsonPath('data.username', $user->username)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.is_following', false)
            ->assertJsonMissingPath('data.email');
    }

    public function test_user_profile_returns_404_for_unknown_username(): void
    {
        $this->getJson('/api/v1/users/nobody-here')
            ->assertNotFound();
    }

    public function test_authenticated_user_sees_is_following_true_when_following(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();

        $viewer->following()->attach($target->id);
        $viewer->increment('following_count');
        $target->increment('followers_count');

        Sanctum::actingAs($viewer, ['*']);

        $this->getJson("/api/v1/users/{$target->username}")
            ->assertOk()
            ->assertJsonPath('data.is_following', true);
    }

    public function test_authenticated_user_sees_is_following_false_when_not_following(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($viewer, ['*']);

        $this->getJson("/api/v1/users/{$target->username}")
            ->assertOk()
            ->assertJsonPath('data.is_following', false);
    }

    public function test_public_profile_includes_follower_and_following_counts(): void
    {
        $user = User::factory()->create(['followers_count' => 5, 'following_count' => 3]);

        $this->getJson("/api/v1/users/{$user->username}")
            ->assertOk()
            ->assertJsonPath('data.followers_count', 5)
            ->assertJsonPath('data.following_count', 3);
    }
}
