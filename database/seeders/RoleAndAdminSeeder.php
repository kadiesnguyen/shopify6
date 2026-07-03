<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $memberRole = Role::findOrCreate('member');
        Role::findOrCreate('shop');

        // Match by username OR email so re-seeding never trips a unique
        // constraint, and never reset an existing account's password.
        $admin = User::query()
            ->where('username', 'admin')
            ->orWhere('email', 'admin@shopi.com')
            ->first() ?? new User([
                'email' => 'admin@shopi.com',
                'password' => Hash::make('Abc@123123'),
            ]);
        $admin->fill([
            'username' => 'admin',
            'user_code' => 'U000001',
            'name' => 'Admin',
            'status' => 'active',
        ])->save();
        $admin->syncRoles([$adminRole]);

        Wallet::query()->firstOrCreate(
            ['user_id' => $admin->id],
            ['balance' => 0, 'balance_pending' => 0, 'balance_frozen' => 0],
        );

        $member = User::query()
            ->where('username', 'member')
            ->orWhere('email', 'member@shopefy.test')
            ->first() ?? new User([
                'email' => 'member@shopefy.test',
                'password' => Hash::make('password'),
            ]);
        $member->fill([
            'username' => 'member',
            'user_code' => 'U000002',
            'name' => 'Member',
            'phone' => '+84901234567',
            'status' => 'active',
        ])->save();
        $member->syncRoles([$memberRole]);

        Wallet::query()->firstOrCreate(
            ['user_id' => $member->id],
            ['balance' => 1250.00, 'balance_pending' => 50.00, 'balance_frozen' => 0],
        );
    }
}
