<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Console\Command;

class EnsureDatabaseReadyCommand extends Command
{
    protected $signature = 'db:ensure-ready
                {--force-seed : Run full DatabaseSeeder even when users exist}';

    protected $description = 'Migrate if needed and guarantee baseline users (admin/member) exist';

    public function handle(): int
    {
        $this->components->info('Ensuring database is migrated...');

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            $this->components->error('sessions table is still missing after migrate — check database/migrations.');

            return self::FAILURE;
        }

        if (! User::query()->exists() || $this->option('force-seed')) {
            $this->components->warn('Database has no users — running full seed.');

            if ($this->call('db:seed', ['--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }

            $this->reportAccounts();

            return self::SUCCESS;
        }

        $this->components->info('Ensuring admin/member accounts exist (idempotent)...');

        if ($this->call('db:seed', [
            '--class' => RoleAndAdminSeeder::class,
            '--force' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->reportAccounts();

        return self::SUCCESS;
    }

    private function reportAccounts(): void
    {
        $admin = User::role('admin')->first();

        if ($admin) {
            $this->components->info('Admin ready: '.$admin->email);
        } else {
            $this->components->error('Admin account missing after ensure-ready — check RoleAndAdminSeeder.');
        }
    }
}
