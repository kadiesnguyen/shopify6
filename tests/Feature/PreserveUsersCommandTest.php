<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreserveUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrate_fresh_runs_during_unit_tests(): void
    {
        User::factory()->create();

        $this->artisan('migrate:fresh', ['--allow-user-loss' => true])
            ->assertSuccessful();
    }

    public function test_migrate_fresh_allowed_with_explicit_flag(): void
    {
        User::factory()->create();

        $this->artisan('migrate:fresh', ['--allow-user-loss' => true])
            ->assertSuccessful();
    }

    public function test_db_upgrade_runs_without_wiping_users(): void
    {
        $user = User::factory()->create(['email' => 'keep-me@test.com']);

        $this->artisan('db:upgrade')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'keep-me@test.com', 'id' => $user->id]);
    }
}
