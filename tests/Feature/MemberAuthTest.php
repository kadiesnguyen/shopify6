<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'member']);
    }

    public function test_login_page_shows_portal_form(): void
    {
        $this->get(route('auth.login'))
            ->assertOk()
            ->assertSee(__('auth_portal.login_title'))
            ->assertSee(__('auth_portal.register_link'))
            ->assertSee('/images/portal/logo.jpg', false)
            ->assertSee('Tiếng Việt');
    }

    public function test_register_page_shows_portal_form(): void
    {
        $this->get(route('auth.register'))
            ->assertOk()
            ->assertSee(__('auth_portal.register_title'))
            ->assertSee(__('auth_portal.terms_label'));
    }

    public function test_member_can_register_with_email(): void
    {
        $response = $this->post(route('auth.register'), [
            'login' => 'seller@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('member.home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'seller@example.com']);
        $this->assertDatabaseHas('wallets', ['user_id' => User::query()->where('email', 'seller@example.com')->value('id')]);
    }

    public function test_member_can_login_with_email(): void
    {
        $user = User::factory()->create(['email' => 'member@shopefy.test']);
        $user->assignRole('member');

        $this->post(route('auth.login'), [
            'login' => 'member@shopefy.test',
            'password' => 'password',
        ])->assertRedirect(route('member.home'))
            ->assertCookie(Auth::guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    public function test_register_requires_terms(): void
    {
        $this->from(route('auth.register'))
            ->post(route('auth.register'), [
                'login' => 'new@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('auth.register'))
            ->assertSessionHasErrors('terms');
    }

    public function test_second_login_invalidates_previous_session(): void
    {
        $user = User::factory()->create(['email' => 'member@shopefy.test']);
        $user->assignRole('member');

        $this->post(route('auth.login'), [
            'login' => 'member@shopefy.test',
            'password' => 'password',
        ])->assertRedirect(route('member.home'));

        $firstSessionId = session()->getId();

        // Simulate a second device: start from a fresh session, otherwise the
        // guest middleware short-circuits the login POST.
        $this->flushSession();
        auth()->logout();

        $this->post(route('auth.login'), [
            'login' => 'member@shopefy.test',
            'password' => 'password',
        ])->assertRedirect(route('member.home'));

        $this->assertDatabaseMissing('sessions', ['id' => $firstSessionId]);
    }
}
