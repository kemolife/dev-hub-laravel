<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_gets_referral_code_on_creation(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->referral_code);
        $this->assertEquals(8, strlen($user->referral_code));
    }

    public function test_referred_user_links_to_referrer_correctly(): void
    {
        $referrer = User::factory()->create();
        $newUser = User::factory()->create(['referred_by_user_id' => $referrer->id]);

        $this->assertEquals($referrer->id, $newUser->referred_by_user_id);
        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'referred_by_user_id' => $referrer->id,
        ]);
    }

    public function test_referral_codes_are_unique_across_users(): void
    {
        $users = User::factory()->count(10)->create();
        $codes = $users->pluck('referral_code')->unique();

        $this->assertCount(10, $codes);
    }
}
