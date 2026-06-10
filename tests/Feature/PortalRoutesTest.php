<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_home_is_public(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_member_home_requires_authentication(): void
    {
        $this->get('/home')->assertRedirect(route('auth.login'));
    }

    public function test_member_can_access_home_after_login(): void
    {
        Role::create(['name' => 'member']);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get('/home')
            ->assertOk();
    }

    public function test_admin_dashboard_requires_admin_role(): void
    {
        Role::create(['name' => 'member']);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        Role::create(['name' => 'admin']);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }
}
