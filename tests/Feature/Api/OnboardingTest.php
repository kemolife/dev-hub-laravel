<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Actions\User\CompleteOnboardingStepAction;
use App\Enums\OnboardingStep;
use App\Events\PostPublished;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_fresh_user_has_empty_onboarding_steps(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/onboarding');

        $response->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('steps', [])
            ->assertJsonStructure(['completed', 'steps', 'all_steps']);
    }

    public function test_publishing_post_auto_completes_first_post_published_step(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $action = app(CompleteOnboardingStepAction::class);
        $action->execute($user, OnboardingStep::FirstPostPublished);

        $user->refresh();

        $this->assertContains(OnboardingStep::FirstPostPublished->value, $user->onboarding_steps);
        $this->assertNull($user->onboarding_completed_at);
    }

    public function test_onboarding_completed_when_all_steps_finished(): void
    {
        $user = User::factory()->create();
        $action = app(CompleteOnboardingStepAction::class);

        foreach (OnboardingStep::cases() as $step) {
            $action->execute($user, $step);
        }

        $user->refresh();

        $this->assertNotNull($user->onboarding_completed_at);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/onboarding');

        $response->assertOk()
            ->assertJsonPath('completed', true);
    }

    public function test_track_onboarding_listener_fires_on_post_published_event(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $user->id]);

        PostPublished::dispatch($post);

        $user->refresh();

        $this->assertContains(OnboardingStep::FirstPostPublished->value, $user->onboarding_steps ?? []);
    }
}
