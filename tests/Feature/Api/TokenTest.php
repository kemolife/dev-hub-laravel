<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_list_their_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('my-app');
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/tokens')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_token_list_does_not_expose_plain_text_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('my-app');
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/tokens')->assertOk();

        foreach ($response->json('data') as $token) {
            $this->assertArrayNotHasKey('token', $token);
        }
    }

    public function test_user_can_create_a_personal_access_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/tokens', ['name' => 'my-integration'])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'my-integration',
        ]);
    }

    public function test_token_creation_respects_abilities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/tokens', [
            'name' => 'read-only',
            'abilities' => ['posts:read'],
        ])->assertCreated();

        $tokenValue = $response->json('data.token');
        $this->assertNotNull($tokenValue);
    }

    public function test_token_creation_requires_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/tokens', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_revoke_their_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('to-revoke');
        Sanctum::actingAs($user, ['*']);

        $this->deleteJson("/api/v1/tokens/{$token->accessToken->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherToken = $other->createToken('other-token');
        Sanctum::actingAs($user, ['*']);

        $this->deleteJson("/api/v1/tokens/{$otherToken->accessToken->id}")
            ->assertNotFound();
    }

    public function test_token_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/tokens')->assertUnauthorized();
        $this->postJson('/api/v1/tokens', ['name' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/v1/tokens/1')->assertUnauthorized();
    }
}
