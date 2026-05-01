<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get('/admin')->assertSuccessful();
    }

    public function test_moderator_user_can_access_admin_panel(): void
    {
        $moderator = User::factory()->create(['role' => Role::Moderator]);

        $this->actingAs($moderator)->get('/admin')->assertSuccessful();
    }

    public function test_member_cannot_access_admin_panel(): void
    {
        $member = User::factory()->create(['role' => Role::Member]);

        $this->actingAs($member)->get('/admin')->assertForbidden();
    }
}
