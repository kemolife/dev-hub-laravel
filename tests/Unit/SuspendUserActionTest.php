<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Admin\SuspendUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SuspendUserActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_suspended_user_is_suspended(): void
    {
        $target = User::factory()->create();
        $actor = User::factory()->create();

        (new SuspendUserAction)->execute($target, $actor, 'Violation of community guidelines');

        $target->refresh();

        $this->assertTrue($target->isSuspended());
        $this->assertNotNull($target->suspended_at);
        $this->assertEquals('Violation of community guidelines', $target->suspension_reason);
    }

    public function test_suspended_user_with_future_until_date_is_suspended(): void
    {
        $target = User::factory()->create();
        $actor = User::factory()->create();
        $until = now()->addDays(7);

        (new SuspendUserAction)->execute($target, $actor, 'Temporary suspension', $until);

        $target->refresh();

        $this->assertTrue($target->isSuspended());
        $this->assertNotNull($target->suspended_until);
    }

    public function test_suspension_creates_audit_log_entry(): void
    {
        $target = User::factory()->create();
        $actor = User::factory()->create();

        (new SuspendUserAction)->execute($target, $actor, 'Spam');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'user.suspended',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);
    }

    public function test_user_without_suspended_at_is_not_suspended(): void
    {
        $user = User::factory()->create(['suspended_at' => null]);

        $this->assertFalse($user->isSuspended());
    }

    public function test_suspension_that_has_expired_is_not_active(): void
    {
        $user = User::factory()->create([
            'suspended_at' => now()->subDays(10),
            'suspended_until' => now()->subDays(1),
        ]);

        $this->assertFalse($user->isSuspended());
    }
}
