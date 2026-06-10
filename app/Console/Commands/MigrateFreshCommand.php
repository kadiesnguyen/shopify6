<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Database\Console\Migrations\FreshCommand;

class MigrateFreshCommand extends FreshCommand
{
    /**
     * @var string
     */
    protected $signature = 'migrate:fresh
                {--database= : The database connection to use}
                {--drop-views : Drop all tables and views}
                {--drop-types : Drop all types (Postgres only)}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--schema-path= : The path to a schema dump file}
                {--seed : Indicates if the seed task should be re-run}
                {--seeder= : The class name of the root seeder}
                {--step : Force the migrations to be run so they can be rolled back individually}
                {--allow-user-loss : Allow wiping existing users (destructive)}';

    public function handle(): int
    {
        if ($this->shouldBlockDestructiveFresh()) {
            $count = User::query()->count();

            $this->components->error("Blocked migrate:fresh: database has {$count} user(s).");
            $this->line('  Users are preserved by default.');
            $this->line('  • Run migrations only:  php artisan migrate');
            $this->line('  • Add demo data safely:  php artisan db:seed');
            $this->line('  • Restore after wipe:    php artisan db:ensure-ready');
            $this->line('  • Force full wipe:       php artisan migrate:fresh --allow-user-loss [--seed]');

            return self::FAILURE;
        }

        return parent::handle();
    }

    private function shouldBlockDestructiveFresh(): bool
    {
        if ($this->option('allow-user-loss')) {
            return false;
        }

        if ($this->laravel->runningUnitTests()) {
            return false;
        }

        return User::query()->exists();
    }
}
