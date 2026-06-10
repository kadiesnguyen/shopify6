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

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@shopi.com'],
            [
                'username' => 'admin',
                'user_code' => 'U000001',
                'name' => 'Admin',
                'phone' => null,
                'password' => Hash::make('Abc@123123'),
                'status' => 'active',
            ],
        );
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

        $member = User::query()->firstOrCreate(
            ['email' => 'member@shopefy.test'],
            [
                'username' => 'member',
                'user_code' => 'U000002',
                'name' => 'Member',
                'phone' => '+84901234567',
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );
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
