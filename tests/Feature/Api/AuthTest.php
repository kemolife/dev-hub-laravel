<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_register_requires_valid_email(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Jane',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Jane',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_two_factor_challenge_when_2fa_enabled(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('two_factor', true)
            ->assertJsonStructure(['two_factor', 'challenge_token']);
    }

    public function test_two_factor_challenge_issues_token_with_valid_code(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $challengeToken = 'test-challenge-uuid';
        Cache::put("2fa_challenge:{$challengeToken}", $user->id, now()->addMinutes(5));

        $provider = $this->mock(TwoFactorAuthenticationProvider::class);
        $provider->shouldReceive('verify')->once()->andReturn(true);

        $response = $this->postJson('/api/v1/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_two_factor_challenge_issues_token_with_valid_recovery_code(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $challengeToken = 'test-challenge-uuid';
        Cache::put("2fa_challenge:{$challengeToken}", $user->id, now()->addMinutes(5));

        $response = $this->postJson('/api/v1/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'recovery_code' => 'recovery-code-1',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_two_factor_challenge_fails_with_invalid_code(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $challengeToken = 'test-challenge-uuid';
        Cache::put("2fa_challenge:{$challengeToken}", $user->id, now()->addMinutes(5));

        $provider = $this->mock(TwoFactorAuthenticationProvider::class);
        $provider->shouldReceive('verify')->once()->andReturn(false);

        $this->postJson('/api/v1/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_two_factor_challenge_fails_with_expired_challenge_token(): void
    {
        $this->postJson('/api/v1/two-factor-challenge', [
            'challenge_token' => 'nonexistent-token',
            'code' => '123456',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['challenge_token']);
    }

    public function test_two_factor_challenge_requires_code_or_recovery_code(): void
    {
        $this->postJson('/api/v1/two-factor-challenge', [
            'challenge_token' => 'some-token',
        ])->assertUnprocessable();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/logout')
            ->assertUnauthorized();
    }
}
