<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Support\PlanLimits;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_free_plan_posts_per_month_returns_5(): void
    {
        $user = User::factory()->make(['plan' => 'free']);

        $limits = PlanLimits::for($user);

        $this->assertSame(5, $limits->postsPerMonth());
    }

    public function test_pro_plan_posts_per_month_returns_null_for_unlimited(): void
    {
        $user = User::factory()->make(['plan' => 'pro']);

        $limits = PlanLimits::for($user);

        $this->assertNull($limits->postsPerMonth());
    }

    public function test_pro_annual_plan_posts_per_month_returns_null_for_unlimited(): void
    {
        $user = User::factory()->make(['plan' => 'pro_annual']);

        $limits = PlanLimits::for($user);

        $this->assertNull($limits->postsPerMonth());
    }

    public function test_free_plan_has_no_api_access(): void
    {
        $user = User::factory()->make(['plan' => 'free']);

        $limits = PlanLimits::for($user);

        $this->assertFalse($limits->hasApiAccess());
    }

    public function test_pro_plan_has_api_access(): void
    {
        $user = User::factory()->make(['plan' => 'pro']);

        $limits = PlanLimits::for($user);

        $this->assertTrue($limits->hasApiAccess());
    }

    public function test_can_create_post_returns_false_when_over_limit(): void
    {
        $user = User::factory()->make(['plan' => 'free']);

        $limits = PlanLimits::for($user);

        $this->assertFalse($limits->canCreatePost(5));
        $this->assertFalse($limits->canCreatePost(10));
    }

    public function test_can_create_post_returns_true_when_under_limit(): void
    {
        $user = User::factory()->make(['plan' => 'free']);

        $limits = PlanLimits::for($user);

        $this->assertTrue($limits->canCreatePost(0));
        $this->assertTrue($limits->canCreatePost(4));
    }

    public function test_pro_plan_can_always_create_post(): void
    {
        $user = User::factory()->make(['plan' => 'pro']);

        $limits = PlanLimits::for($user);

        $this->assertTrue($limits->canCreatePost(1000));
    }

    public function test_at_soft_limit_returns_true_at_80_percent(): void
    {
        $user = User::factory()->make(['plan' => 'free']); // limit = 5

        $limits = PlanLimits::for($user);

        // 80% of 5 = 4
        $this->assertTrue($limits->atSoftLimit(4, 'posts_per_month'));
        $this->assertTrue($limits->atSoftLimit(5, 'posts_per_month'));
    }

    public function test_at_soft_limit_returns_false_below_80_percent(): void
    {
        $user = User::factory()->make(['plan' => 'free']); // limit = 5

        $limits = PlanLimits::for($user);

        // Below 80% of 5 = 4
        $this->assertFalse($limits->atSoftLimit(3, 'posts_per_month'));
        $this->assertFalse($limits->atSoftLimit(0, 'posts_per_month'));
    }

    public function test_at_soft_limit_returns_false_for_unlimited_plan(): void
    {
        $user = User::factory()->make(['plan' => 'pro']);

        $limits = PlanLimits::for($user);

        $this->assertFalse($limits->atSoftLimit(1000, 'posts_per_month'));
    }

    public function test_null_plan_defaults_to_free_limits(): void
    {
        $user = User::factory()->make(['plan' => null]);

        $limits = PlanLimits::for($user);

        $this->assertSame(5, $limits->postsPerMonth());
        $this->assertFalse($limits->hasApiAccess());
    }
}
