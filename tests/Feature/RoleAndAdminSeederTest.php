<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAndAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_reset_existing_admin_password(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        User::factory()->create([
            'email' => 'admin@shopi.com',
            'password' => Hash::make('custom-password'),
            'status' => 'active',
        ])->assignRole('admin');

        $this->seed(RoleAndAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@shopi.com')->first();

        $this->assertTrue(Hash::check('custom-password', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
    }
}
