<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_submit_feedback(): void
    {
        $response = $this->postJson('/api/v1/feedback', [
            'type' => 'bug',
            'description' => 'Something is broken on the homepage.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('feedback', [
            'type' => 'bug',
            'description' => 'Something is broken on the homepage.',
            'user_id' => null,
        ]);
    }

    public function test_authenticated_user_submits_feedback_with_metadata(): void
    {
        $user = User::factory()->create(['email' => 'dev@example.com']);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/feedback', [
            'type' => 'feature',
            'description' => 'Please add dark mode support.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('feedback', [
            'type' => 'feature',
            'user_id' => $user->id,
            'email' => 'dev@example.com',
        ]);

        $feedback = Feedback::first();
        $this->assertNotNull($feedback->metadata);
        $this->assertArrayHasKey('ip', $feedback->metadata);
        $this->assertArrayHasKey('app_version', $feedback->metadata);
    }

    public function test_invalid_type_returns_422(): void
    {
        $response = $this->postJson('/api/v1/feedback', [
            'type' => 'spam',
            'description' => 'Not a valid type.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }
}
