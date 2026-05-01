<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewFollowerNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_follow_another_user(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $followee = User::factory()->create();
        Sanctum::actingAs($follower, ['*']);

        $response = $this->postJson("/api/v1/users/{$followee->username}/follow");

        $response->assertOk()
            ->assertJsonPath('following', true)
            ->assertJsonPath('followers_count', 1);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'followee_id' => $followee->id,
        ]);

        Notification::assertSentTo($followee, NewFollowerNotification::class);
    }

    public function test_following_again_unfollows_the_user(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $followee = User::factory()->create();
        Sanctum::actingAs($follower, ['*']);

        $this->postJson("/api/v1/users/{$followee->username}/follow");

        $response = $this->postJson("/api/v1/users/{$followee->username}/follow");

        $response->assertOk()
            ->assertJsonPath('following', false)
            ->assertJsonPath('followers_count', 0);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'followee_id' => $followee->id,
        ]);
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/users/{$user->username}/follow");

        $response->assertUnprocessable();
    }

    public function test_followers_count_updates_correctly_on_follow_and_unfollow(): void
    {
        Notification::fake();

        $follower = User::factory()->create();
        $followee = User::factory()->create();
        Sanctum::actingAs($follower, ['*']);

        $this->postJson("/api/v1/users/{$followee->username}/follow");

        $followee->refresh();
        $follower->refresh();

        $this->assertEquals(1, $followee->followers_count);
        $this->assertEquals(1, $follower->following_count);

        $this->postJson("/api/v1/users/{$followee->username}/follow");

        $followee->refresh();
        $follower->refresh();

        $this->assertEquals(0, $followee->followers_count);
        $this->assertEquals(0, $follower->following_count);
    }

    public function test_personal_feed_returns_posts_from_followed_users(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $followed = User::factory()->create();
        $other = User::factory()->create();

        $user->following()->attach($followed->id);

        $followedPost = Post::factory()->published()->create(['user_id' => $followed->id]);
        Post::factory()->published()->create(['user_id' => $other->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $followedPost->public_id);
    }

    public function test_guest_can_list_followers_of_a_user(): void
    {
        $user = User::factory()->create();
        $followers = User::factory()->count(3)->create();

        foreach ($followers as $follower) {
            $follower->following()->attach($user->id);
        }

        $response = $this->getJson("/api/v1/users/{$user->username}/followers");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_can_list_users_a_user_is_following(): void
    {
        $user = User::factory()->create();
        $followees = User::factory()->count(2)->create();

        foreach ($followees as $followee) {
            $user->following()->attach($followee->id);
        }

        $response = $this->getJson("/api/v1/users/{$user->username}/following");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
