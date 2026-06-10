<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleAndAdminSeeder::class,
            LanguageSeeder::class,
            CmsSeeder::class,
            SiteSettingsSeeder::class,
            DemoDataSeeder::class,
            SieummoPortalSeeder::class,
        ]);
    }
}
