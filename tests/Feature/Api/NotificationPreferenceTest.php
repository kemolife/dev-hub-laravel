<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_list_notification_preferences(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/notification-preferences');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['type', 'channel', 'enabled', 'digest']]]);
    }

    public function test_guest_cannot_list_preferences(): void
    {
        $this->getJson('/api/v1/notification-preferences')->assertUnauthorized();
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'type' => NotificationType::NewCommentOnPost->value,
                    'channel' => NotificationChannel::Mail->value,
                    'enabled' => false,
                    'digest' => false,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Preferences updated.');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'type' => NotificationType::NewCommentOnPost->value,
            'channel' => NotificationChannel::Mail->value,
            'enabled' => false,
        ]);
    }

    public function test_updating_preferences_upserts_rather_than_duplicates(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'type' => NotificationType::NewFollower,
            'channel' => NotificationChannel::Database,
            'enabled' => true,
        ]);

        $this->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'type' => NotificationType::NewFollower->value,
                    'channel' => NotificationChannel::Database->value,
                    'enabled' => false,
                ],
            ],
        ])->assertOk();

        $this->assertSame(1, NotificationPreference::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'type' => NotificationType::NewFollower->value,
            'channel' => NotificationChannel::Database->value,
            'enabled' => false,
        ]);
    }

    public function test_update_rejects_invalid_notification_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'type' => 'not_a_valid_type',
                    'channel' => NotificationChannel::Mail->value,
                    'enabled' => true,
                ],
            ],
        ])->assertUnprocessable();
    }

    public function test_preferences_index_returns_defaults_for_unconfigured_types(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // No stored preferences
        $response = $this->getJson('/api/v1/notification-preferences');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // All returned preferences should default enabled=true
        foreach ($data as $pref) {
            $this->assertTrue($pref['enabled']);
        }
    }
}
