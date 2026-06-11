<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\BaselineDatabaseSeeder;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Console\Command;

class EnsureDatabaseReadyCommand extends Command
{
    protected $signature = 'db:ensure-ready
                {--force-seed : Run full DatabaseSeeder even when data exists (requires --allow-data-loss)}
                {--allow-data-loss : Allow destructive reseed when using --force-seed}';

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

        if ($this->option('force-seed')) {
            if (! $this->option('allow-data-loss')) {
                $this->components->error('Refusing --force-seed without --allow-data-loss.');

                return self::FAILURE;
            }

            $this->components->warn('Running full DatabaseSeeder (--allow-data-loss).');

            if ($this->call('db:seed', [
                '--force' => true,
                '--allow-data-loss' => true,
            ]) !== self::SUCCESS) {
                return self::FAILURE;
            }

            $this->reportAccounts();

            return self::SUCCESS;
        }

        if (! User::query()->exists()) {
            if (\App\Support\Database\DatabaseGuard::hasPreservedData()) {
                $this->components->warn('Database has no users but preserved data exists — running baseline seed only.');

                if ($this->call('db:seed', [
                    '--class' => BaselineDatabaseSeeder::class,
                    '--force' => true,
                ]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
            } else {
                $this->components->warn('Database has no users — running full seed.');

                if ($this->call('db:seed', ['--force' => true]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
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
