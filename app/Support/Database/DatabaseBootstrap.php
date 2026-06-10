<?php

namespace App\Support\Database;

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseBootstrap
{
    private static bool $ran = false;

    public static function ensureReady(): void
    {
        if (self::$ran || app()->runningUnitTests() || app()->environment('testing')) {
            return;
        }

        self::$ran = true;

        if (! self::shouldAutoBootstrap()) {
            return;
        }

        try {
            if (self::missingCoreTables()) {
                Artisan::call('migrate', ['--force' => true]);
            }

            if (! Schema::hasTable('users')) {
                return;
            }

            if (! User::query()->exists()) {
                Log::warning('DatabaseBootstrap: database has no users, running ensure-ready.');
                Artisan::call('db:ensure-ready');

                return;
            }

            if (! User::role('admin')->exists()) {
                Log::warning('DatabaseBootstrap: admin account missing, seeding RoleAndAdminSeeder only.');
                Artisan::call('db:seed', [
                    '--class' => RoleAndAdminSeeder::class,
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('DatabaseBootstrap failed: '.$exception->getMessage());
        }
    }

    private static function shouldAutoBootstrap(): bool
    {
        if (app()->environment('production', 'testing')) {
            return false;
        }

        return app()->environment('local', 'development') && filter_var(env('DB_AUTO_BOOTSTRAP', true), FILTER_VALIDATE_BOOL);
    }

    /** @return list<string> */
    private static function coreTables(): array
    {
        return ['users', 'sessions', 'migrations'];
    }

    private static function missingCoreTables(): bool
    {
        foreach (self::coreTables() as $table) {
            if (! Schema::hasTable($table)) {
                return true;
            }
        }

        return false;
    }
}
