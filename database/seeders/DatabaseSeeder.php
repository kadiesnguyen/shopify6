<?php

namespace Database\Seeders;

use App\Support\Database\DatabaseGuard;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(BaselineDatabaseSeeder::class);

        if (DatabaseGuard::hasCommerceData()) {
            return;
        }

        $this->call([
            DemoDataSeeder::class,
            SieummoPortalSeeder::class,
        ]);
    }
}
