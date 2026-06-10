<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SyncShopRoles extends Command
{
    protected $signature = 'shops:sync-roles';

    protected $description = 'Assign the shop role to every user who owns a shop record';

    public function handle(): int
    {
        Role::findOrCreate('shop');

        $count = 0;

        User::query()
            ->whereHas('shop')
            ->each(function (User $user) use (&$count): void {
                if ($user->hasRole('shop')) {
                    return;
                }

                $user->assignRole('shop');
                $count++;
            });

        $this->info("Assigned shop role to {$count} user(s).");

        return self::SUCCESS;
    }
}
