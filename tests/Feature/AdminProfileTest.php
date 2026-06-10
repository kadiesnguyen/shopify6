<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        $this->admin = User::factory()->create([
            'email' => 'admin@shopi.com',
            'password' => 'Abc@123123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_profile_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee(__('admin.profile.title'))
            ->assertSee('admin@shopi.com');
    }

    public function test_admin_can_change_password_from_profile(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/profile/password', [
                'current_password' => 'Abc@123123',
                'password' => 'NewPass@456',
                'password_confirmation' => 'NewPass@456',
            ])
            ->assertRedirect(route('admin.profile.show'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPass@456', $this->admin->fresh()->password));
    }

    public function test_admin_users_are_hidden_from_user_list(): void
    {
        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');

        $this->assertFalse(
            User::query()->withoutAdmins()->whereKey($this->admin->id)->exists()
        );

        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee($member->email);
    }

    public function test_seeded_admin_uses_shopi_credentials(): void
    {
        $this->seed(RoleAndAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@shopi.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('Abc@123123', $admin->password));
    }
}
