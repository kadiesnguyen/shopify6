<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_returns_token(): void
    {
        Role::create(['name' => 'member']);

        $user = User::factory()->create([
            'email' => 'api@shopefy.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $user->assignRole('member');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'api@shopefy.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email']]);
    }

    public function test_api_register_creates_member(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'newapi',
            'name' => 'New API User',
            'email' => 'newapi@shopefy.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'newapi@shopefy.test']);
    }

    public function test_authenticated_profile_endpoint(): void
    {
        Role::create(['name' => 'member']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('member');

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_member_dashboard_api(): void
    {
        Role::create(['name' => 'member']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('member');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/member/dashboard')
            ->assertOk()
            ->assertJsonStructure(['wallet', 'order_counts', 'products']);
    }
}
