<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AiConversation;

use App\Models\AiConversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationVisibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function owner_can_view_private_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->private()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk();
    }

    #[Test]
    public function other_user_cannot_view_private_conversation(): void
    {
        $conversation = AiConversation::factory()->private()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertForbidden();
    }

    #[Test]
    public function other_user_can_view_public_conversation(): void
    {
        $conversation = AiConversation::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->public_id}")
            ->assertOk();
    }

    #[Test]
    public function post_conversations_list_excludes_other_users_private_conversations(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::factory()->published()->create();

        AiConversation::factory()->for($owner)->for($post)->create(['is_private' => false]);
        AiConversation::factory()->for($owner)->for($post)->private()->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/posts/{$post->slug}/conversations")
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }
}
