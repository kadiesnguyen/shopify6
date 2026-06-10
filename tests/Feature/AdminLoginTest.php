<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);

        $this->admin = User::factory()->create([
            'email' => 'admin@shopefy.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_admin_login_page_shows_remember_me_option(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee(__('auth.remember_me'));
    }

    public function test_admin_login_with_remember_sets_remember_cookie(): void
    {
        $response = $this->post(route('admin.login'), [
            'email' => 'admin@shopefy.test',
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_login_without_remember_still_sets_remember_cookie(): void
    {
        $response = $this->post(route('admin.login'), [
            'email' => 'admin@shopefy.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($this->admin);
    }
}
