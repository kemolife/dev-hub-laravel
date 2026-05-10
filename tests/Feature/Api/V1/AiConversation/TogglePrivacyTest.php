<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TogglePrivacyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function owner_can_toggle_conversation_to_private(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->create(['is_private' => false]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk()
            ->assertJsonPath('data.is_private', true);

        $this->assertDatabaseHas('ai_conversations', [
            'id' => $conversation->id,
            'is_private' => true,
        ]);
    }

    #[Test]
    public function non_owner_cannot_toggle_privacy(): void
    {
        $conversation = AiConversation::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->patchJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertForbidden();
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $conversation = AiConversation::factory()->create();

        $this->patchJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertUnauthorized();
    }

    #[Test]
    public function owner_can_toggle_conversation_back_to_public(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->private()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk()
            ->assertJsonPath('data.is_private', false);

        $this->assertDatabaseHas('ai_conversations', [
            'id' => $conversation->id,
            'is_private' => false,
        ]);
    }
}
