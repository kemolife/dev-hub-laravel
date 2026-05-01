<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_feature_flags_return_correct_active_state(): void
    {
        $user = User::factory()->create();

        // Flags defined as off by default
        $this->assertFalse(Feature::for($user)->active('new-editor'));
        $this->assertFalse(Feature::for($user)->active('ai-summaries'));

        // Flags defined as on by default
        $this->assertTrue(Feature::for($user)->active('recommendations'));
        $this->assertTrue(Feature::for($user)->active('public-roadmap'));
    }

    public function test_pennant_feature_active_can_be_overridden(): void
    {
        $user = User::factory()->create();

        Feature::for($user)->activate('new-editor');

        $this->assertTrue(Feature::for($user)->active('new-editor'));
    }

    public function test_feature_flags_endpoint_returns_active_flags(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/features');

        $response->assertOk()
            ->assertJsonStructure(['features'])
            ->assertJsonFragment(['features' => ['recommendations', 'public-roadmap']]);
    }
}
