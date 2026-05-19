<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Actions\Billing\StartTrialAction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_get_billing_info(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        $this->actingAs($user)
            ->getJson('/api/v1/billing')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'plan',
                    'plan_name',
                    'trial_ends_at',
                    'on_trial',
                    'subscribed',
                    'subscription_status',
                    'limits' => ['posts_per_month', 'api_access'],
                ],
            ])
            ->assertJsonPath('data.plan', 'free')
            ->assertJsonPath('data.plan_name', 'Free');
    }

    public function test_unauthenticated_user_cannot_get_billing_info(): void
    {
        $this->getJson('/api/v1/billing')->assertUnauthorized();
    }

    public function test_checkout_endpoint_requires_valid_plan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/billing/checkout', ['plan' => 'invalid_plan'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);
    }

    public function test_checkout_rejects_free_plan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/billing/checkout', ['plan' => 'free'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);
    }

    public function test_trial_starts_on_registration(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Trial User',
            'username' => 'trialuser',
            'email' => 'trialuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'trialuser@example.com')->firstOrFail();

        $this->assertNotNull($user->trial_ends_at);
        $this->assertTrue($user->trial_ends_at->isFuture());
        $this->assertEqualsWithDelta(
            Carbon::now()->addDays(14)->timestamp,
            $user->trial_ends_at->timestamp,
            5,
        );
    }

    public function test_start_trial_action_sets_trial_end_date(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);
        $action = app(StartTrialAction::class);

        $action->execute($user);

        $user->refresh();
        $this->assertNotNull($user->trial_ends_at);
        $this->assertTrue($user->trial_ends_at->isFuture());
    }

    public function test_billing_info_shows_on_trial_when_trial_active(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => Carbon::now()->addDays(7),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/billing')
            ->assertOk()
            ->assertJsonPath('data.on_trial', true);
    }

    public function test_billing_info_shows_not_on_trial_when_trial_expired(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => Carbon::now()->subDays(1),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/billing')
            ->assertOk()
            ->assertJsonPath('data.on_trial', false);
    }
}
