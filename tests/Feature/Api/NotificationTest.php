<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\Comment;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\NewCommentOnYourPost;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_list_their_notifications(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Seed two notifications directly
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewCommentOnYourPost',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['type' => 'new_comment_on_post'],
        ]);

        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['type' => 'new_follower'],
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'type', 'data', 'read_at', 'created_at']]]);
    }

    public function test_guest_cannot_list_notifications(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $notificationId = (string) Str::uuid();
        DatabaseNotification::create([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\NewCommentOnYourPost',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['type' => 'new_comment_on_post'],
            'read_at' => null,
        ]);

        $this->assertNull(DatabaseNotification::find($notificationId)->read_at);

        $response = $this->postJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertOk()
            ->assertJsonPath('message', 'Notification marked as read.');

        $this->assertNotNull(DatabaseNotification::find($notificationId)->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        foreach (range(1, 3) as $i) {
            DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\NewFollower',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => ['type' => 'new_follower'],
                'read_at' => null,
            ]);
        }

        $this->assertSame(3, $user->unreadNotifications()->count());

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('message', 'All notifications marked as read.');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_can_delete_a_notification(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $notificationId = (string) Str::uuid();
        DatabaseNotification::create([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\NewFollower',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['type' => 'new_follower'],
        ]);

        $this->deleteJson("/api/v1/notifications/{$notificationId}")
            ->assertNoContent();

        $this->assertNull(DatabaseNotification::find($notificationId));
    }

    public function test_user_cannot_access_another_users_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $notificationId = (string) Str::uuid();
        DatabaseNotification::create([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\NewFollower',
            'notifiable_type' => User::class,
            'notifiable_id' => $other->id,
            'data' => ['type' => 'new_follower'],
        ]);

        $this->postJson("/api/v1/notifications/{$notificationId}/read")
            ->assertNotFound();
    }

    public function test_notification_respects_user_preference_when_email_is_disabled(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // Disable mail for new_comment_on_post
        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationType::NewCommentOnPost,
            'channel' => NotificationChannel::Mail,
            'enabled' => false,
        ]);

        $notification = new NewCommentOnYourPost(
            Comment::factory()->make(['user_id' => $user->id + 1])
        );

        $channels = $notification->via($user);

        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
    }
}
