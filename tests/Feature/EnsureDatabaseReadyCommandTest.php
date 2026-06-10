<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnsureDatabaseReadyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);
    }

    public function test_ensure_ready_seeds_when_no_users(): void
    {
        $this->artisan('db:ensure-ready')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@shopi.com']);
        $this->assertDatabaseHas('users', ['email' => 'member@shopefy.test']);
    }

    public function test_ensure_ready_preserves_existing_users(): void
    {
        $user = User::factory()->create(['email' => 'keep-me@test.com']);

        $this->artisan('db:ensure-ready')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'keep-me@test.com', 'id' => $user->id]);
        $this->assertDatabaseHas('users', ['email' => 'admin@shopi.com']);
    }

    public function test_ensure_ready_recreates_missing_admin(): void
    {
        User::factory()->create(['email' => 'other@test.com']);

        $this->artisan('db:ensure-ready')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@shopi.com']);
    }
}
