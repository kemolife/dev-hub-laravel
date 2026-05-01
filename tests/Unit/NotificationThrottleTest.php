<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NotificationType;
use App\Models\User;
use App\Support\NotificationThrottle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationThrottleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_allows_first_notification_through(): void
    {
        $user = User::factory()->create();

        $allowed = NotificationThrottle::allow($user, NotificationType::NewCommentOnPost);

        $this->assertTrue($allowed);
    }

    public function test_blocks_after_max_per_hour_is_reached(): void
    {
        $user = User::factory()->create();
        $type = NotificationType::NewCommentOnPost;

        // Allow up to max
        foreach (range(1, 3) as $i) {
            NotificationThrottle::allow($user, $type, maxPerHour: 3);
        }

        $blocked = NotificationThrottle::allow($user, $type, maxPerHour: 3);

        $this->assertFalse($blocked);
    }

    public function test_different_types_have_independent_limits(): void
    {
        $user = User::factory()->create();

        // Exhaust NewFollower limit
        foreach (range(1, 2) as $i) {
            NotificationThrottle::allow($user, NotificationType::NewFollower, maxPerHour: 2);
        }

        // NewCommentOnPost should still be allowed
        $allowed = NotificationThrottle::allow($user, NotificationType::NewCommentOnPost, maxPerHour: 2);

        $this->assertTrue($allowed);
    }

    public function test_different_users_have_independent_limits(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $type = NotificationType::NewFollower;

        // Exhaust userA limit
        foreach (range(1, 2) as $i) {
            NotificationThrottle::allow($userA, $type, maxPerHour: 2);
        }

        // userB should still be allowed
        $allowed = NotificationThrottle::allow($userB, $type, maxPerHour: 2);

        $this->assertTrue($allowed);
    }
}
