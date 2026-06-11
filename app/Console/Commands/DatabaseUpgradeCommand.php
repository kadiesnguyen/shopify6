<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseUpgradeCommand extends Command
{
    protected $signature = 'db:upgrade {--seed : Run seeders after migrating}';

    protected $description = 'Run pending migrations and optionally seed without wiping users';

    public function handle(): int
    {
        $this->components->info('Running migrations (users preserved)...');

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->option('seed')) {
            $this->components->info('Seeding baseline data only (users/products preserved)...');

            if ($this->call('db:seed', [
                '--class' => 'Database\\Seeders\\BaselineDatabaseSeeder',
                '--force' => true,
            ]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $this->components->info('Done.');

        return self::SUCCESS;
    }
}
